# Membrr

Membrr is a plugin for [ExpressionEngine](http://www.expressionengine.com) that brings subscription membership
website functionality to your ExpressionEngine installation.  More specifically, it is a
[module](http://expressionengine.com/public_beta/docs/development/modules.html), [extension](http://expressionengine.com/docs/development/extensions.html),
and [control panel](http://expressionengine.com/public_beta/docs/development/modules.html#control_panel_file) for ExpressionEngine.

Membrr uses the free, open source[OpenGateway billing engine](http://www.github.com/electricfunction/opengateway) to handle all of its billing, automated emails, and
payment gateway integration. OpenGateway is downloaded
and installed in a sub-folder or sub-domain of your website prior to setting up your Membrr plugin.

## Documentation

All documentation is included in this release, in the [docs folder](/docs/).

## FAQs

### Will using Membrr break any of my existing addons for ExpressionEngine?

No. There are no known conflicts.  You can use Solspace's User module, Cartthrob, or any other EE addon without interfering with Membrr.
Why?  Membrr adds functionality to EE's basic member system without interfering with the member system at all.  Other modules won't even know
Membrr exists (and that's a good thing, in this case).

### Why build Membrr using underlying OpenGateway technology?

By allowing OpenGateway to handle all of the complicated, multi-gateway billing parts of the plugin, we free ourselves up
from having to develop all of this code with the ExpressionEngine framework.  We can also do things like port the code to new
platforms (like from EE1.6.x to EE2.0) in a _much_ shorter timeframe.

Finally, by using OpenGateway, we give you an enterprise-class billing engine and API that you can use to extend your
Membrr-powered website or on new projects.

## History

EE Donations was developed by [Brock Ferguson](http://www.brockferguson.com), founder of Electric Function, Inc. After Electric Function was acquired, EE Donations was open-sourced by the new owners.