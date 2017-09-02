# Wordpress-Plugin-Debugger
A mu-plugin that will automatically disable plugins that throw errors.  Built to help prevent white screens and help debugging.  It's in development and I'd like to make it part of a bigger project.


# Long story short, this plugin is designed to disable plugins that are throwing uncaught errors.

When a plugin throws an uncaught exception, it will trigger this code.  The first time it does it, a user may encounter a white screen or error output, depending on your server.   At that time, the plugin is automatically disabled. This does not errors related to your primary database connectivity since there is an row saved in the WordPress options table to identify the problem.

In your plugins screen, you will see plugins that are actively disabled with a red background.  You may re-enable them by clicking activate again.  If they throw an error, they will be disabled again. 

Looking for input.  I'd love to build it out more if there is any need for it.
