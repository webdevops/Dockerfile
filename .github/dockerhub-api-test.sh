#!/usr/bin/env bash
# TEMPORARY: tries every known way to change the description of a repository (REPOSITORY) on Docker Hub with one pair
# of credentials (HUB_USERNAME, HUB_TOKEN) and prints a table of Docker Hub's answers, also to the job summary. Every
# write sends the description the repository already has, so nothing visible changes. Names, e-mail addresses and
# tokens are never printed, only their length and the start of their SHA-256. Remove before merging.
set -uo pipefail

HUB=https://hub.docker.com
REPOSITORY=${REPOSITORY:-webdevops/php}
NAMESPACE=${REPOSITORY%%/*}
NAME=${REPOSITORY#*/}
SUMMARY=${GITHUB_STEP_SUMMARY:-/dev/null}
TMP=$(mktemp -d)
WRITES_ACCEPTED=0

fingerprint() { printf %s "$1" | sha256sum | cut -c1-8; }
oneline() { tr -d '\r' | tr '\n' ' ' | head -c 220; }
row() {
    printf '%-26s %-52s %-4s %s\n' "$1" "$2" "$3" "$4"
    printf '| %s | `%s` | %s | %s |\n' "$1" "$2" "$3" "${4//|/\\|}" >> "$SUMMARY"
}

# kind and scope of a JWT from Docker Hub, without names, e-mail addresses or ids
claims() {
    local payload
    payload=$(cut -d. -f2 <<<"$1" | tr '_-' '/+')
    while (( ${#payload} % 4 )); do payload+='='; done
    base64 -d <<<"$payload" 2>/dev/null | jq -c '{
        source: ((.source.type? // .["https://hub.docker.com"].source? // null) | tostring | sub("\\|.*"; "")),
        scope: (.scope // null)
    }' 2>/dev/null || echo '(not a JWT)'
}

echo "### ${CREDENTIALS:-credentials}" >> "$SUMMARY"
if [[ -z "${HUB_USERNAME:-}" || -z "${HUB_TOKEN:-}" ]]; then
    echo "not set, skipped" | tee -a "$SUMMARY"
    exit 0
fi
info="username: ${#HUB_USERNAME} characters, sha256 $(fingerprint "$HUB_USERNAME"); token: ${#HUB_TOKEN} characters, starts with ${HUB_TOKEN:0:9}, sha256 $(fingerprint "$HUB_TOKEN")"
echo "$info"
printf '%s\n\n| Login | Call | HTTP | Answer |\n| --- | --- | --- | --- |\n' "$info" >> "$SUMMARY"

# --- logins: label -> value of the Authorization header
declare -A AUTH
login() { # label, url, JSON body, field of the token in the answer
    local code token
    code=$(curl -sS -o "$TMP/login.json" -w '%{http_code}' -H 'Content-Type: application/json' --data "$3" "$2")
    token=$(jq -r --arg field "$4" '.[$field] // empty' "$TMP/login.json" 2>/dev/null)
    if [[ -n "$token" ]]; then
        echo "::add-mask::$token"
        LOGIN_TOKEN=$token
        row "$1" "POST ${2#"$HUB"}" "$code" "token $(claims "$token")"
    else
        LOGIN_TOKEN=
        row "$1" "POST ${2#"$HUB"}" "$code" "$(oneline < "$TMP/login.json")"
    fi
}

login 'auth/token' "$HUB/v2/auth/token" \
    "$(jq -nc --arg i "$HUB_USERNAME" --arg s "$HUB_TOKEN" '{identifier: $i, secret: $s}')" access_token
[[ -n "$LOGIN_TOKEN" ]] && AUTH['auth/token, Bearer']="Bearer $LOGIN_TOKEN"

login 'users/login (deprecated)' "$HUB/v2/users/login" \
    "$(jq -nc --arg u "$HUB_USERNAME" --arg p "$HUB_TOKEN" '{username: $u, password: $p}')" token
if [[ -n "$LOGIN_TOKEN" ]]; then
    AUTH['users/login, Bearer']="Bearer $LOGIN_TOKEN"
    AUTH['users/login, JWT']="JWT $LOGIN_TOKEN"
fi


# --- what the repository has now; every write sends it back unchanged
code=$(curl -sS -o "$TMP/current.json" -w '%{http_code}' "$HUB/v2/namespaces/$NAMESPACE/repositories/$NAME")
if [[ "$code" != 200 ]] || ! jq -e '.full_description != null' "$TMP/current.json" > /dev/null; then
    echo "::error::cannot read the current description of $REPOSITORY (HTTP $code)"
    exit 1
fi
jq -c '{description, full_description}' "$TMP/current.json" > "$TMP/body.json"
jq -rj --arg namespace "$NAMESPACE" --arg name "$NAME" '
    "intent=" + ("update-overview" | tojson | @uri)
    + "&namespace=" + ($namespace | tojson | @uri)
    + "&name=" + ($name | tojson | @uri)
    + "&fullDescription=" + (.full_description | tojson | @uri)
' "$TMP/current.json" > "$TMP/body.form"

call() { # login label, Authorization header, method, url, content type, body file
    local code answer
    code=$(curl -sS -o "$TMP/answer" -w '%{http_code}' -X "$3" -H "Authorization: $2" \
        ${5:+-H "Content-Type: $5"} ${6:+--data-binary "@$6"} \
        -H "Origin: $HUB" -H "Referer: $HUB/repository/docker/$NAMESPACE/$NAME/general" "$4")
    if [[ "$code" == 2* ]]; then
        if [[ "$3" == GET ]]; then
            answer="permissions $(jq -c '.permissions // empty' "$TMP/answer" 2>/dev/null)"
        else
            answer='accepted'
            WRITES_ACCEPTED=$((WRITES_ACCEPTED + 1))
        fi
    else
        answer=$(oneline < "$TMP/answer")
    fi
    row "$1" "$3 ${4#"$HUB"}" "$code" "$answer"
}

for label in 'auth/token, Bearer' 'users/login, Bearer' 'users/login, JWT'; do
    if [[ -z "${AUTH[$label]:-}" ]]; then
        row "$label" '-' '-' 'no token from this login'
        continue
    fi
    header=${AUTH[$label]}
    call "$label" "$header" GET "$HUB/v2/namespaces/$NAMESPACE/repositories/$NAME"
    call "$label" "$header" PATCH "$HUB/v2/namespaces/$NAMESPACE/repositories/$NAME" application/json "$TMP/body.json"
    call "$label" "$header" PATCH "$HUB/v2/repositories/$NAMESPACE/$NAME" application/json "$TMP/body.json"
    call "$label" "$header" PATCH "$HUB/v2/repositories/$NAMESPACE/$NAME/" application/json "$TMP/body.json"
    call "$label" "$header" POST "$HUB/resources/repository/$NAMESPACE/$NAME/edit.data" \
        'application/x-www-form-urlencoded;charset=UTF-8' "$TMP/body.form"
done

echo
echo "writes accepted: $WRITES_ACCEPTED"
printf '\nwrites accepted: %s\n' "$WRITES_ACCEPTED" >> "$SUMMARY"
(( WRITES_ACCEPTED > 0 ))
