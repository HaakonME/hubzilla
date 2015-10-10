[b]Hubzilla on OpenShift[/b]
You will notice a new .openshift folder when you fetch from upstream, i.e. from [url=https://github.com/redmatrix/hubzilla.git]https://github.com/redmatrix/hubzilla.git[/url] , which contains a deploy script to set up Hubzilla on OpenShift.

Create an account on OpenShift, then use the registration e-mail and password to create your first Hubzilla instance. Install git and RedHat's command line tools - rhc - if you have not already done so.

[code]rhc app-create your_app_name php-5.4 mysql-5.5 cron phpmyadmin --namespace your_domain --from-code https://github.com/redmatrix/hubzilla.git -l your@email.address -p your_account_password
[/code]

Make a note of the database username and password OpenShift creates for your instance, and use these at [url=https://your_app_name-your_domain.rhcloud.com/]https://your_app_name-your_domain.rhcloud.com/[/url] to complete the setup.

NOTE: PostgreSQL is NOT support yet, see [zrl=https://zot-mor.rhcloud.com/display/3c7035f2a6febf87057d84ea0ae511223e9b38dc27913177bc0df053edecac7c@zot-mor.rhcloud.com?zid=haakon%40zot-mor.rhcloud.com]this thread[/zrl].

[b]Update[/b]
To update, consider your own workflow first. I have forked Hubzilla code into my GitHub account to be able to try things out, this remote repo is called origin. Here is how I fetch new code from upstream, merge into my local repo, then push the updated code both into origin and the remote repo called openshift.

[code]git fetch upstream;git checkout master;git merge upstream/master;git push origin;git push openshift HEAD
[/code]

[b]Administration[/b]
Symptoms of need for MySQL database administration are:
[list]
[*] you can visit your domain and see the Hubzilla frontpage, but trying to login throws you back to login. This can mean your session table is marked as crashed.
[*] you can login, but your channel posts are not visible. This can mean your item table is marked as crashed.
[*] you can login and you can see your channel posts, but apparently nobody is getting your posts, comments, likes and so on. This can mean your outq table is marked as crashed.
[/list]

[b]How to fix crashed tables in MySQL[/b]
Using MySQL and the MyISAM database engine can result in table indexes coming out of sync, and you have at least two options for fixing tables marked as crashed.
[list]
[*] Use the database username and password OpenShift creates for your instance at [url=https://your_app_name-your_domain.rhcloud.com/phpmyadmin/]https://your_app_name-your_domain.rhcloud.com/phpmyadmin/[/url] to login via the web into your phpMyAdmin web interface, click your database in the left column, in the right column scroll down to the bottom of the list of tables and click the checkbox for marking all tables, then select Check tables from the drop down menu. This will check the tables for problems, and you can then checkmark only those tables with problems, and select Repair table from the same drop down menu at the bottom.
[*] You can login to your instance with SSH - see OpenShift for details - then

[code]cd mysql/data/your_database
myisamchk -r *.MYI[/code]

or if you get

[code]Can't create new tempfile[/code]

check your OpenShift's gear quota with

[code]quota -gus[/code]

and if you are short on space, then locally (not SSH) do

[code]rhc app-tidy your_app_name -l your_login -p your_password[/code]

to have rhc delete temporary files and OpenShift logs to free space first, then check the size of your local repo dir and execute

[code]git gc[/code]

against it and check the size again, and then to minimize your remote repo connect via SSH to your application gear and execute the same command against it by changing to the remote repo directory - your repo should be in

[code]~/git/your_app_name.git[/code]

(if not, do find -size +1M to find it), then do

[code]
cd
cd mysql/data/yourdatabase
myisamchk -r -v -f*.MYI[/code]

and hopefully your database tables are now okay.
[/list]

[b]Notes[/b]
[list]
[*] definitely DO turn off feeds and discovery by default if you are on the Free or Bronze plan on OpenShift with a single 1Gb gear by visiting [observer.baseurl]admin/site when logged in as administrator of your Hubzilla site. 
[*] DO add the above defaults into the deploy script.
[*] DO add git gc to the deploy script
[*] MAYBE DO add myisamchk - only checking? to the end of the deploy script.
[/list]
