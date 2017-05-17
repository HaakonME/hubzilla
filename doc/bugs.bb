[h2]Bugs, Issues, and things that go bump in the night...[/h2]
[h3]Something went wrong! Who is charge of fixing it?[/h3]

[b]$Projectname Community Server[/b]

$Projectname Community Server is open source software which is maintained by "the community" - essentially unpaid volunteers. Nobody is in charge of fixing bugs. We work together to keep the software and network running smoothly and error free. You are a member of this community, so we also require your help to provide quality software. There are no mythical "developers" who magically fix everything. It's up to all of us to pitch in and help.   

The first thing you need to do is talk to your hub administrator - the person who runs and manages your site. They are in the unique position of having access to the internal software and database and [b]logfiles[/b] and will need to be involved in fixing your problem. Other people "on the net" can't really help with this. The first thing the hub administrator needs to do is look at their logs and/or try to reproduce the problem. So try to be as helpful and courteous as possible in helping them look into the problem. 

To find your hub administrator (if you don't know who they are) please look at [url=[baseurl]/siteinfo]this page[/url]. If they have not provided any contact info on that page or provided an "Impressum" there, see [url=[baseurl]/siteinfo.json]this site info summary[/url] under the heading "admin:".

It is highly recommended that bug reports be filed by hub administrators so that they can include the relevant logfile and database information relevant to the issue and be available to try workarounds and followup tests. Without this level of cooperation it may not be possible to fix the problem.  

[h3]I'm a hub administrator; what do I do?[/h3]

The software instructions which provide this web service are open source and are available for your inspection. We encourage everybody to read it and see how everything works and verify that we aren't doing anything evil or negligent with your personal communications. If an error message was reported, often one can do a search on the source files for that error message and find out what triggered it. With this information and the site logfiles it may be possible to figure out the sequence of events leading to the error. There could also be other sites involved, and the problem may not even be on your site but elsewhere in the network. Try to pin down the communication endpoints (hubs or sites) involved in the problem and contact the administrator of that site or those sites. Please try and provide an event time of when things went wrong so it can be found in the logs. Work with the other administrator(s) to try and find the cause of the problem. Logfiles are your friend. When something happens in the software that we didn't expect, it is nearly always logged. 

[h3]The white screen of death[/h3]

If you get a blank white screen when doing something, this is almost always a code or syntax error. There are instructions in the site .htconfig.php file which will allow the site administrator to enabling syntax logging. We recommend all sites use this. With syntax logging enabled repeat the sequence which led to the error and it should log the offending line of code. Hopefully you will be able to fix the problem with this information. When you do, please submit the fix "upstream" so that we can share the fix with the rest of the project members and other communities. This is a key benefit of using open source software - we share with each other and everybody benefits.

[h3]I'm stumped. I can't figure out what is wrong.[/h3]

At this point it might be worthwhile discussing the issue on one of the online forums. There may be several of these and some may be more suited to your spoken language. At this time, the 'Hubzilla Support' channel (support@gravizot.de) is the recommended forum for discussing bugs. 

If community members with software engineering training/expertise can't help you right away, understand that they are volunteers and may have a lot of other work and demands on their time. At this point you need to file a bug report. You will need an account on github.com to do this. So register, and then visit https://github.com/redmatrix/hubzilla/issues . Create an issue here and provide all the same information that you provided online. Don't leave out anything. 

Then you wait. If it's a high profile issue, it may get fixed quickly. But nobody is in charge of fixing bugs. If it lingers without resolution, please spend some more time investigating the problem. Ask about anything you don't understand related to the behaviour. You will learn more about how the software works and quite possibly figure out why it isn't working now. Ultimately it is somebody in the community who is going to fix this and you are a member of the community; and this is how the open source process works. 


Other people working to fix the problem may need to find out more, so do your homework and document what is happening and everything you've tried. Don't say "I did xyz and it didn't work." That doesn't tell us anything. Tell us precisely what steps you took and what you expected the result to be, and precisely what happened as a result. What page/URL were you looking at or what form were you filling in? If there were any error messages, don't say "there was an error message". Tell us exactly what the message said. Also tell us what hub you are using, what software version you're running and any other details that may be unique about your site configuration. It is understood that you might wish to keep some information and your connections private, however if you aren't willing to share the information other people need to reproduce/fix the problem, it may not get fixed. 
       