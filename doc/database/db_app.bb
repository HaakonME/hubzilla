[table]
[tr][th]Field[/th][th]Description[/th][th]Type[/th][th]Null[/th][th]Key[/th][th]Default[/th][th]Extra
[/th][/tr]
[tr][td]id[/td][td]generated index[/td][td]int(11)[/td][td]NO[/td][td]PRI[/td][td]NULL[/td][td]auto_increment
[/td][/tr]
[tr][td]app_id[/td][td]hash identifying this app[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]app_sig[/td][td]currently unused[/td][td]char(255)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]app_author[/td][td]xchan_hash of app creator[/td][td]char(255)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]app_name[/td][td]name of app[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]app_desc[/td][td]optional description of app[/td][td]text[/td][td]NO[/td][td][/td][td]NULL[/td][td]
[/td][/tr]
[tr][td]app_url[/td][td]target_url[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]app_photo[/td][td]app icon[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]app_version[/td][td]version of app[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]app_channel[/td][td]channel_id owning this instance of the app[/td][td]int(11)[/td][td]NO[/td][td]MUL[/td][td]0[/td][td]
[/td][/tr]
[tr][td]app_addr[/td][td]reddress/webbie of app creator[/td][td]char(255)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]app_price[/td][td]free-form price field[/td][td]char(255)[/td][td]NO[/td][td]MUL[/td][td][/td][td]
[/td][/tr]
[tr][td]app_page[/td][td]currently unused[/td][td]char(255)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]app_requires[/td][td]access rules[/td][td]char(255)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]

[tr][td]app_created[/td][td]datetime of app creation[/td][td]datetime[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]app_edited[/td][td]datetime of last app edit[/td][td]datetime[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]

[tr][td]app_deleted[/td][td]1 = deleted, 0 = normal[/td][td]int(11)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]
[tr][td]app_system[/td][td]1 = imported system app, 0 = member created app[/td][td]int(11)[/td][td]NO[/td][td][/td][td][/td][td]
[/td][/tr]


[/table]

Storage for personal apps

Return to [zrl=[baseurl]/help/database]database documentation[/zrl]