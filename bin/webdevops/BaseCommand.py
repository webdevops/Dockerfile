from cleo import Command, Output
import os
import sys

class BaseCommand(Command):
    configuration = False

    def __init__(self, configuration):
        """
        Constructor
        """
        Command.__init__(self)
        self.configuration = configuration

    def get_configuration(self):
        """
        Get configuration
        """
        return self.configuration


    def get_whitelist(self):
        """
        Get whitelist
        """
        return self.option('whitelist')

    def get_blacklist(self):
        """
        Get blacklist
        """
        ret = self.option('blacklist')

        # static BLACKLIST file
        if os.path.isfile(self.configuration['blacklistFile']):
            with open(self.configuration['blacklistFile'], 'r') as ins:
                for line in ins:
                    ret.append(line)

        return ret
