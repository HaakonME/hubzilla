### To-be-organized information

**Here is how you can join us.**

First, get yourself a working git package on the system where you will be
doing development.

Create your own github account.

You may fork/clone the Red repository from [https://github.com/redmatrix/hubzilla.git](https://github.com/redmatrix/hubzilla.git).

Follow the instructions provided here: [http://help.github.com/fork-a-repo/](http://help.github.com/fork-a-repo/)
to create and use your own tracking fork on github

Then go to your github page and create a "Pull request" when you are ready
to notify us to merge your work.


**Important**

Please pull in any changes from the project repository and merge them with your work **before** issuing a pull request. We reserve the right to reject any patch which results in a large number of merge conflicts. This is especially true in the case of language translations - where we may not be able to understand the subtle differences between conflicting versions.

Also - **test your changes**. Don't assume that a simple fix won't break something else. If possible get an experienced Red developer to review the code. 

**How to theme Hubzilla**

This is a short documentation on what I found while trying to modify Hubzilla's appearance.

First, you'll need to create a new theme. This is in /view/theme, and I chose to copy 'redbasic' since it's the only available for now. Let's assume I named it .

Oh, and don't forget to rename the _init function in /php/theme.php to be _init() instead of redbasic_init().

At that point, if you need to add javascript or css files, add them to /js or /css, and then "register" them in _init() through head_add_js('file.js') and head_add_css('file.css').

Now you'll probably want to alter a template. These can be found in in /view/tpl OR view//tpl. All you should have to do is copy whatever you want to tweak from the first place to your theme's own tpl directory.


We're pretty relaxed when it comes to developers. We don't have a lot of rules. Some of us are over-worked and if you want to help we're happy to let you help. That said, attention to a few guidelines will make the process smoother and make it easier to work together. We have developers from across the globe with different abilities and different cultural backgrounds and different levels of patience. Our primary rule is to respect others. Sometimes this is hard and sometimes we have very different opinions of how things should work, but if everybody makes an effort, we'll get along just fine.  

**Here is how you can join us.**

First, get yourself a working git package on the system where you will be
doing development.

Create your own github account.

You may fork/clone the Red repository from [url=https://github.com/redmatrix/hubzilla.git]https://github.com/redmatrix/hubzilla.git[/url]

Follow the instructions provided here: [url=http://help.github.com/fork-a-repo/]http://help.github.com/fork-a-repo/[/url]
to create and use your own tracking fork on github

Then go to your github page and create a "Pull request" when you are ready
to notify us to merge your work.


**Important**

Please pull in any changes from the project repository and merge them with your work **before** issuing a pull request. We reserve the right to reject any patch which results in a large number of merge conflicts. This is especially true in the case of language translations - where we may not be able to understand the subtle differences between conflicting versions.

Also - **test your changes**. Don't assume that a simple fix won't break something else. If possible get an experienced Red developer to review the code. 

Further documentation can be found at the Github wiki pages at: [url=https://github.com/friendica/red/wiki]https://github.com/friendica/red/wiki[/url]

**Concensus Building**

Code changes which fix an obvious bug are pretty straight-forward. For instance if you click "Save" and the thing you're trying to save isn't saved, it's fairly obvious what the intended behaviour should be. Often when developing feature requests, it may affect large numbers of community members and it's possible that other members of the community won't agree with the need for the feature, or with your proposed implementation. They may not see something as a bug or a desirable feature.

We encourage consensus building within the community when it comes to any feature which might be considered controversial or where there isn't unanimous decision that the proposed feature is the correct way to accomplish the task. The first place to pitch your ideas is to [url=https://zothub.com/channel/one]Channel One[/url]. Others may have some input or be able to point out facets of your concept which might be problematic in our environment. But also, you may encounter opposition to your plan. This doesn't mean you should stop and/or ignore the feature. Listen to the concerns of others and try and work through any implementation issues. 

There are places where opposition cannot be resolved. In these cases, please consider making your feature **optional** or non-default behaviour that must be specifically enabled. This technique can often be used when a feature has significant but less than unanimous support. Those who desire the feature can turn it on and those who don't want it - will leave it turned off.

If a feature uses other networks or websites and or is only seen as desirable by a small minority of the community, consider making the functionality available via an addon or plugin. Once again, those who don't desire the feature won't need to install it. Plugins are relatively easy to create and "hooks" can be easily added or modified if the current hooks do not do what is needed to allow your plugin to work.
     
