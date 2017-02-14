[h3]Who is a $Projectname developer? Should I read this?[/h3]

Anyone who contributes to making $Projectname better is a developer. There are many different and important ways you can contribute to this amazing technology, [i]even if you do not know how to write code[/i]. The software itself is only a part of the $Projectname project. You can contribute by 
[list]
[*] translating text to your language so that people around the world have the opportunity to use $Projectname
[*] promoting $Projectname and spreading awareness of the platform through blog posts, articles, and word-of-mouth
[*] creating artwork and graphics for project assets such as icons and marketing material
[*] supporting project infrastructure like the project website and demo servers
[/list]
[i]Software[/i] developers are of course welcomed; there are so many great ideas to implement and not enough people to make them all a reality! The $Projectname code base is an advanced and mature system, but the platform is still very flexible and responsive to new ideas. 

We're pretty relaxed when it comes to developers. We don't have a lot of rules. Some of us are over-worked and if you want to help we're happy to let you help. That said, attention to a few guidelines will make the process smoother and make it easier to work together. All developers are expected to abide by our [zrl=[baseurl]/help/developer/covenant]code of conduct[/zrl]. We have developers from across the globe with different abilities and different cultural backgrounds and different levels of patience. Our primary rule is to respect others. Sometimes this is hard and sometimes we have very different opinions of how things should work, but if everybody makes an effort, we'll get along just fine. 

This document will help you get started learning and contributing to $Projectname.

[h3]Versions and Releases[/h3]

$Projectname currently uses a standard version numbering sequence of $x.$y(.$z), for instance '1.12' or '1.12.1'. The first digit is the major version number. Major versions are released "roughly" once per year; often in December.

The second digit is the minor release number. If this number is odd, it is a development version. If the number is even, it is a released version. Minor versions are released (moved from dev to master) typically once per month when development is 'stable', but this is likely to increase. Going forward minor releases will be made somewhere between one and three months; corresponding to a stable code point and when there is general community consensus that the current code base is stable enough to consider a release.

The final digit is an interface or patch designator. 

The release process involves changing the version number (by definition the minor version number will be odd, and the minor number will be incremented). Once a year for a major release the major version will be incremented, and the minor number reset to 0. 

The release candidate is moved to a new branch; and testing will commence/continue for a period of 1-2 weeks afterward or until any significant issues have been resolved. This branch is usually labelled with RC (release candidate); for instance 1.8RC represents the pending release of version 1.8. At this time, the minor version number on the dev branch is incremented to the next odd number. (For instance 1.9). New development can then take place in the dev branch. 

Bug fixes should always be applied to 'dev' and from there merged forward (typically with git cherry-pick) to the RC branch and if necessary applied to the master or official release branch.

At the time a release candidate is produced, the language strings file is frozen until a release is made. Translation work may continue, but all translations should be submitted to 'dev' and merged forward to RC.

Once RC testing is completed, RC is merged to 'master' and the RC version designator removed; resulting in one final checkin to change the version number. The CHANGELOG file should also be updated at or just prior to this time. If there are merge conflicts during this final merge, the merge will be abandoned; and 'git merge -s ours' applied. This results in a replacement of master with the contents of the RC branch. Conflicts often arise with string updates which were made to master after the last release and cannot easily be resolved without hand editing. Since this is a release of tested code, hand editing is discouraged, and the replacement merge strategy should be used instead. It is assumed that RC now contains the most recent well-tested code. 

Once the release is live and merged to master, the RC branch may be removed. 

Fixes may be made to master after release. Where possible these should be made to dev and 'git cherry-pick' used to merge forward; which preserves the commit info and prevents merge conflicts in the next cycle. Only rarely does a patch only apply to the master branch. If necessary this can be made. If the change is severe, the interface version number should be incremented. This is at the discretion of the community. In any event, a 'git pull' of the master branch should always result in the latest release with any post-release patches applied. 

The interface number (the $z in $x.$y.$z) should be incremented in dev whenever a change is made which changes the interfaces or API in incompatible ways so that any external packages (especially addons and API clients) relying on a the current behaviour can discover and change their own interfaces accordingly at the point that it changed.  

[h3]Git repository branches[/h3]

There are two official branches of the $Projectname git repo. 
[list]
[*] The stable version is maintained on the [b]master[/b] branch. The latest commit in this branch is considered to be suitable for production hubs. 
[*] Experimental development occurs on the [b]dev[/b] branch, which is merged into [b]master[/b] when it is deemed tested and stable enough.
[/list]

[h3]Developer tools and workflows[/h3]

[h4]Hub Snapshots[/h4]

The [url=[baseurl]/help/admin/hub_snapshots]hub snapshots[/url] page provides instructions and scripts for taking complete snapshots of a hub to support switching between consistent and completely known states. This is useful to prevent situations where the content or database schema might be incompatible with the code. 

[h3]Translations[/h3]

Our translations are managed through Transifex. If you wish to help out translating $Projectname to another language, sign up on transifex.com, visit [url=https://www.transifex.com/Friendica/red-matrix/]Transifex[/url] and request to join one of the existing language teams or create a new one. Notify one of the core developers when you have a translation update which requires merging, or ask about merging it yourself if you're comfortable with git and PHP. We have a string file called 'messages.po' which is gettext compliant and a handful of email templates, and from there we automatically generate the application's language files.   

[h4]Translation Process[/h4]

The strings used in the UI of $Projectname is translated at [url=https://www.transifex.com/Friendica/red-matrix/]Transifex[/url] and then
included in the git repository at github. If you want to help with translation
for any language, be it correcting terms or translating $Projectname to a
currently not supported language, please register an account at transifex.com
and contact the Redmatrix translation team there.

Translating $Projectname is simple. Just use the online tool at transifex. If you
don't want to deal with git & co. that is fine, we check the status of the[/td][/tr]
[tr]ranslations regularly and import them into the source tree at github so that
others can use them.

We do not include every translation from transifex in the source tree to avoid
a scattered and disturbed overall experience. As an uneducated guess we have a
lower limit of 50% translated strings before we include the language. This
limit is judging only by the amount of translated strings under the assumption[/td][/tr]
[tr]hat the most prominent strings for the UI will be translated first by a[/td][/tr]
[tr]ranslation team. If you feel your translation useable before this limit,
please contact us and we will probably include your teams work in the source[/td][/tr]
[tr]ree.

If you want to get your work into the source tree yourself, feel free to do so
and contact us with and question that arises. The process is simple and
$Projectname ships with all the tools necessary.

The location of the translated files in the source tree is
    /view/LNG-CODE/
where LNG-CODE is the language code used, e.g. de for German or fr for French.
For the email templates (the *.tpl files) just place them into the directory
and you are done. The translated strings come as a "hmessages.po" file from[/td][/tr]
[tr]ransifex which needs to be translated into the PHP file $Projectname uses.  To do
so, place the file in the directory mentioned above and use the "po2php"
utility from the util directory of your $Projectname installation.

Assuming you want to convert the German localization which is placed in
view/de/hmessages.po you would do the following.

1. Navigate at the command prompt to the base directory of your
   $Projectname installation

2. Execute the po2php script, which will place the translation
   in the hstrings.php file that is used by $Projectname.

       $> php util/po2php.php view/de/hmessages.po

   The output of the script will be placed at view/de/hstrings.php where
   froemdoca os expecting it, so you can test your translation mmediately.
                                  
3. Visit your $Projectname page to check if it still works in the language you
   just translated. If not try to find the error, most likely PHP will give
   you a hint in the log/warnings.about the error.
                                        
   For debugging you can also try to "run" the file with PHP. This should
   not give any output if the file is ok but might give a hint for
   searching the bug in the file.

       $> php view/de/hstrings.php

4. commit the two files with a meaningful commit message to your git
   repository, push it to your fork of the $Projectname repository at github and
   issue a pull request for that commit.

[h4]Utilities[/h4]

Additional to the po2php script there are some more utilities for translation
in the "util" directory of the $Projectname source tree.  If you only want to[/td][/tr]
[tr]ranslate $Projectname into another language you wont need any of these tools most
likely but it gives you an idea how the translation process of $Projectname
works.

For further information see the utils/README file.

[h4]Known Problems[/h4]

* $Projectname uses the language setting of the visitors browser to determain the
  language for the UI. Most of the time this works, but there are some known
  quirks.
* the early translations are based on the friendica translations, if you 
  some rough translations please let us know or fix them at Transifex.

[h3]Licensing[/h3]

All code contributed to the project falls under the MIT license, unless otherwise specified. We will accept third-party code which falls under MIT, BSD and LGPL, but copyleft licensing (GPL, and AGPL) is only permitted in addons. It must be possible to completely remove the GPL (copyleft) code from the main project without breaking anything.

[h3]Coding Style[/h3]

In the interests of consistency we adopt the following code styling. We may accept patches using other styles, but where possible please try to provide a consistent code style. We aren't going to argue or debate the merits of this style, and it is irrelevant what project 'xyz' uses. This is not project 'xyz'. This is a baseline to try and keep the code readable now and in the future. 
[list]
[*]All comments should be in English.
[*]We use doxygen to generate documentation. This hasn't been consistently applied, but learning it and using it are highly encouraged.
[*]Indentation is accomplished primarily with tabs using a tab-width of 4.
[*]String concatenation and operators should be separated by whitespace. e.g. "$foo = $bar . 'abc';" instead of "$foo=$bar.'abc';"
[*]Generally speaking, we use single quotes for string variables and double quotes for SQL statements. "Here documents" should be avoided. Sometimes using double quoted strings with variable replacement is the most efficient means of creating the string. In most cases, you should be using single quotes.
[*]Use whitespace liberally to enhance readability. When creating arrays with many elements, we will often set one key/value pair per line, indented from the parent line appropriately. Lining up the assignment operators takes a bit more work, but also increases readability.
[*]Generally speaking, opening braces go on the same line as the thing which opens the brace. They are the last character on the line. Closing braces are on a line by themselves. 
[*]Some functions take arguments in argc/argv style like main() in C or function args in bash or Perl. Urls are broken up within a module. e.g, given "http://example.com/module/arg1/arg2", then $this->argc will be 3 (integer) and $this->argv will contain:   [0] => 'module', [1] => 'arg1',  [2] => 'arg2'. There will always be one argument. If provided a naked domain  URL, $this->argv[0] is set to "home".
[/list]

[h3]File system layout[/h3]
[table border=0]
[th]Directory[/th][th]Description[/th][/tr]
[tr][td]addon[/td][td]optional addons/plugins[/td][/tr]
[tr][td]boot.php[/td][td]Every process uses this to bootstrap the application structure[/td][/tr]
[tr][td]doc[/td][td]Help Files[/td][/tr]
[tr][td]images[/td][td]core required images[/td][/tr]
[tr][td]include[/td][td]The "model" in MVC - (back-end functions), also contains PHP "executables" for background processing[/td][/tr]
[tr][td]index.php[/td][td]The front-end controller for web access[/td][/tr]
[tr][td]install[/td][td]Installation and upgrade files and DB schema[/td][/tr]
[tr][td]library[/td][td]Third party modules (must be license compatible)[/td][/tr]
[tr][td]mod[/td][td]Controller modules based on URL pathname (e.g. [url=http://sitename/foo]http://sitename/foo[/url] loads mod/foo.php)[/td][/tr]
[tr][td]mod/site/[/td][td]site-specific mod overrides, excluded from git[/td][/tr]
[tr][td]util[/td][td]translation tools, main English string database and other miscellaneous utilities[/td][/tr]
[tr][td]version.inc[/td][td]contains current version (auto-updated via cron for the master repository and distributed via git)[/td][/tr]
[tr][td]view[/td][td]theming and language files[/td][/tr]
[tr][td]view/(css,js,img,php,tpl)[/td][td]default theme files[/td][/tr]
[tr][td]view/(en,it,es ...)[/td][td]language strings and resources[/td][/tr]
[tr][td]view/theme/[/td][td]individual named themes containing (css,js,img,php,tpl) over-rides[/td][/tr]
[/table]

[b][url=[baseurl]/help/developer/unorganized]More information needing re-organization and updating...[/url][/b]
