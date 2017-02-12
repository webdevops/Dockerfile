#
# Rspec methods
#


# Get content of url
#
# Example:
#   get_url("http://localhost/")
#     => 1
def get_url(url)
    cmd = command("curl -- %s" % Shellwords.escape(url))
    expect(cmd.exit_status).to eq 0

    return cmd.stdout
end

# Get pid of running service or process
#
# Example:
#   service_get_pid("java")
#     => 1
def service_get_pid(process)
    cmd = command("pidof %s" % Shellwords.escape(process))
    expect(cmd.exit_status).to eq 0

    pid = cmd.stdout
    pid.strip!

    expect(pid).to match(%r!^[0-9]+$!)
    expect(pid).not_to match(%r!^[0]+$!)

    return pid
end

# Check if service/process is running stable for some time
#
# Example:
#   service_running_check("java")
def check_if_service_is_running_stable(process, wait_time = 10)
    # check if service is up for the first time
    service_pid = service_get_pid(process)

    # wait some seconds to check if process was restarted
    sleep(wait_time)

    # recheck if service is still running
    check_pid = service_get_pid(process)
    expect(service_pid).to eq(check_pid)
end
