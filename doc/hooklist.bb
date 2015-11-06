[zrl=[baseurl]/help/hook/module_mod_aftercontent]module_mod_aftercontent[/zrl]
	General purpose hook for any module, executed after mod_content(). Replace 'module' with module name, e.g. 'photos_mod_aftercontent'.

[zrl=[baseurl]/help/hook/module_mod_content]module_mod_content[/zrl]
	General purpose hook for any module, executed before mod_content(). Replace 'module' with module name, e.g. 'photos_mod_content'.

[zrl=[baseurl]/help/hook/module_mod_init]module_mod_init[/zrl]
	General purpose hook for any module, executed before mod_init(). Replace 'module' with module name, e.g. 'photos_mod_init'.

[zrl=[baseurl]/help/hook/module_mod_post]module_mod_post[/zrl]
	General purpose hook for any module, executed before mod_post(). Replace 'module' with module name, e.g. 'photos_mod_post'.

[zrl=[baseurl]/help/hook/about_hook]about_hook[/zrl]
	Called from the siteinfo page

[zrl=[baseurl]/help/hook/accept_follow]accept_follow[/zrl]
	Called when accepting a connection (friend request)

[zrl=[baseurl]/help/hook/account_downgrade]account_downgrade[/zrl]
	Called when an account has expired, indicating a potential downgrade to "basic" service class

[zrl=[baseurl]/help/hook/account_settings]account_settings[/zrl]
	Called when account settings have been saved

[zrl=[baseurl]/help/hook/activity_received]activity_received[/zrl]
	Called when an activity (post, comment, like, etc.) has been received from a zot source

[zrl=[baseurl]/help/hook/affinity_labels]affinity_labels[/zrl]
	Used to generate alternate labels for the affinity slider.

[zrl=[baseurl]/help/hook/api_perm_is_allowed]api_perm_is_allowed[/zrl]
	Called when perm_is_allowed() is executed from an API call.

[zrl=[baseurl]/help/hook/app_menu]app_menu[/zrl]
	Used to register plugins as apps

[zrl=[baseurl]/help/hook/atom_author]atom_author[/zrl]
	Called when generating an author or owner element for an Atom ActivityStream feed

[zrl=[baseurl]/help/hook/atom_entry]atom_entry[/zrl]
	Called when generating each item entry of an Atom ActivityStreams feed

[zrl=[baseurl]/help/hook/atom_feed]atom_feed[/zrl]
	Called when generating an Atom ActivityStreams feed

[zrl=[baseurl]/help/hook/atom_feed_end]atom_feed_end[/zrl]
	Called when generation of an Atom ActivityStreams feed is completed

[zrl=[baseurl]/help/hook/attach_upload_file]attach_upload_file[/zrl]
	Called when uploading a file

[zrl=[baseurl]/help/hook/authenticate]authenticate[/zrl]
	Can provide alternate authentication mechanisms

[zrl=[baseurl]/help/hook/avatar_lookup]avatar_lookup[/zrl]
	Used for "gravatar" or libravatar profile photo lookup.
 
[zrl=[baseurl]/help/hook/bb2diaspora]bb2diaspora[/zrl]
	called when converting bbcode to markdown

[zrl=[baseurl]/help/hook/bbcode]bbcode[/zrl]
	Called when converting bbcode to HTML

[zrl=[baseurl]/help/hook/channel_remove]channel_remove[/zrl]
	Called when removing a channel

[zrl=[baseurl]/help/hook/chat_message]chat_message[/zrl]
	Called to create a chat message.

[zrl=[baseurl]/help/hook/chat_post]chat_post[/zrl]
	Called when a chat message has been posted

[zrl=[baseurl]/help/hook/check_account_email]check_account_email[/zrl]
	Validate the email provided in an account registration

[zrl=[baseurl]/help/hook/check_account_invite]check_account_invite[/zrl]
	Validate an invitation code when using site invitations	

[zrl=[baseurl]/help/hook/check_account_password]check_account_password[/zrl]
	Used to provide policy control over account passwords (minimum length, character set inclusion, etc.)

[zrl=[baseurl]/help/hook/connect_premium]connect_premium[/zrl]
	Called when connecting to a premium channel

[zrl=[baseurl]/help/hook/connector_settings]connector_settings[/zrl]
	Called when posting to the features/addon settings page

[zrl=[baseurl]/help/hook/construct_page]construct_page[/zrl]
	General purpose hook to provide content to certain page regions. Called when constructing the Comanche page. 

[zrl=[baseurl]/help/hook/contact_block_end]contact_block_end[/zrl]
	Called when generating the sidebar "Connections" widget

[zrl=[baseurl]/help/hook/contact_edit]contact_edit[/zrl]
	Called when editing a connection via connedit

[zrl=[baseurl]/help/hook/contact_edit_post]contact_edit_post[/zrl]
	Called when posting to connedit

[zrl=[baseurl]/help/hook/contact_select_options]contact_select_options[/zrl]
	Deprecated/unused

[zrl=[baseurl]/help/hook/conversation_start]conversation_start[/zrl]
	Called in the beginning of rendering a conversation (message or message collection or stream)	

[zrl=[baseurl]/help/hook/create_identity]create_identity[/zrl]
	Called when creating a channel

[zrl=[baseurl]/help/hook/cron]cron[/zrl]
	Called when scheduled tasks (poller) is executed

[zrl=[baseurl]/help/hook/cron_daily]cron_daily[/zrl]
	Called when daily scheduled tasks are executed

[zrl=[baseurl]/help/hook/cron_weekly]cron_weekly[/zrl]
	Called when weekly scheduled tasks are executed

[zrl=[baseurl]/help/hook/directory_item]directory_item[/zrl]
	Called when generating a directory listing for display

[zrl=[baseurl]/help/hook/discover_by_webbie]discover_by_webbie[/zrl]
	Called when performing a webfinger lookup

[zrl=[baseurl]/help/hook/display_item]display_item[/zrl]
	
[zrl=[baseurl]/help/hook/display_settings]display_settings[/zrl]

[zrl=[baseurl]/help/hook/display_settings_post]display_settings_post[/zrl]

[zrl=[baseurl]/help/hook/donate_contributors]donate_contributors[/zrl]

[zrl=[baseurl]/help/hook/donate_plugin]donate_plugin[/zrl]

[zrl=[baseurl]/help/hook/donate_sponsors]donate_sponsors[/zrl]

[zrl=[baseurl]/help/hook/dreport_is_storable]dreport_is_storable[/zrl]

[zrl=[baseurl]/help/hook/drop_item]drop_item[/zrl]

[zrl=[baseurl]/help/hook/enotify]enotify[/zrl]

[zrl=[baseurl]/help/hook/enotify_mail]enotify_mail[/zrl]

[zrl=[baseurl]/help/hook/enotify_store]enotify_store[/zrl]

[zrl=[baseurl]/help/hook/event_created]event_created[/zrl]

[zrl=[baseurl]/help/hook/event_updated]event_updated[/zrl]

[zrl=[baseurl]/help/hook/externals_url_select]externals_url_select[/zrl]

[zrl=[baseurl]/help/hook/feature_enabled]feature_enabled[/zrl]

[zrl=[baseurl]/help/hook/feature_settings]feature_settings[/zrl]

[zrl=[baseurl]/help/hook/feature_settings_post]feature_settings_post[/zrl]

[zrl=[baseurl]/help/hook/follow]follow[/zrl]

[zrl=[baseurl]/help/hook/follow_allow]follow_allow[/zrl]

[zrl=[baseurl]/help/hook/gender_selector]gender_selector[/zrl]

[zrl=[baseurl]/help/hook/gender_selector_min]gender_selector_min[/zrl]

[zrl=[baseurl]/help/hook/generate_map]generate_map[/zrl]

[zrl=[baseurl]/help/hook/generate_named_map]generate_named_map[/zrl]

[zrl=[baseurl]/help/hook/get_all_api_perms]get_all_api_perms[/zrl]

[zrl=[baseurl]/help/hook/get_all_perms]get_all_perms[/zrl]

[zrl=[baseurl]/help/hook/get_features]get_features[/zrl]

[zrl=[baseurl]/help/hook/get_role_perms]get_role_perms[/zrl]

[zrl=[baseurl]/help/hook/get_widgets]get_widgets[/zrl]

[zrl=[baseurl]/help/hook/global_permissions]global_permissions[/zrl]

[zrl=[baseurl]/help/hook/home_content]home_content[/zrl]

[zrl=[baseurl]/help/hook/home_init]home_init[/zrl]

[zrl=[baseurl]/help/hook/hostxrd]hostxrd[/zrl]

[zrl=[baseurl]/help/hook/html2bbcode]html2bbcode[/zrl]

[zrl=[baseurl]/help/hook/identity_basic_export]identity_basic_export[/zrl]

[zrl=[baseurl]/help/hook/import_author_xchan]import_author_xchan[/zrl]

[zrl=[baseurl]/help/hook/import_channel]import_channel[/zrl]

[zrl=[baseurl]/help/hook/import_directory_profile]import_directory_profile[/zrl]

[zrl=[baseurl]/help/hook/import_xchan]import_xchan[/zrl]

[zrl=[baseurl]/help/hook/item_photo_menu]item_photo_menu[/zrl]

[zrl=[baseurl]/help/hook/item_store]item_store[/zrl]

[zrl=[baseurl]/help/hook/item_store_update]item_store_update[/zrl]

[zrl=[baseurl]/help/hook/item_translate]item_translate[/zrl]

[zrl=[baseurl]/help/hook/jot_networks]jot_networks[/zrl]

[zrl=[baseurl]/help/hook/jot_tool]jot_tool[/zrl]

[zrl=[baseurl]/help/hook/load_pdl]load_pdl[/zrl]

[zrl=[baseurl]/help/hook/local_dir_update]local_dir_update[/zrl]

[zrl=[baseurl]/help/hook/logged_in]logged_in[/zrl]

[zrl=[baseurl]/help/hook/logging_out]logging_out[/zrl]

[zrl=[baseurl]/help/hook/login_hook]login_hook[/zrl]

[zrl=[baseurl]/help/hook/magic_auth]magic_auth[/zrl]

[zrl=[baseurl]/help/hook/magic_auth_openid_success]magic_auth_openid_success[/zrl]

[zrl=[baseurl]/help/hook/magic_auth_success]magic_auth_success[/zrl]

[zrl=[baseurl]/help/hook/main_slider]main_slider[/zrl]

[zrl=[baseurl]/help/hook/marital_selector]marital_selector[/zrl]

[zrl=[baseurl]/help/hook/marital_selector_min]marital_selector_min[/zrl]

[zrl=[baseurl]/help/hook/module_loaded]module_loaded[/zrl]

[zrl=[baseurl]/help/hook/mood_verbs]mood_verbs[/zrl]

[zrl=[baseurl]/help/hook/nav]nav[/zrl]

[zrl=[baseurl]/help/hook/network_content_init]network_content_init[/zrl]

[zrl=[baseurl]/help/hook/network_ping]network_ping[/zrl]

[zrl=[baseurl]/help/hook/network_tabs]network_tabs[/zrl]

[zrl=[baseurl]/help/hook/network_to_name]network_to_name[/zrl]

[zrl=[baseurl]/help/hook/notifier_end]notifier_end[/zrl]

[zrl=[baseurl]/help/hook/notifier_hub]notifier_hub[/zrl]

[zrl=[baseurl]/help/hook/notifier_normal]notifier_normal[/zrl]

[zrl=[baseurl]/help/hook/obj_verbs]obj_verbs[/zrl]

[zrl=[baseurl]/help/hook/oembed_probe]oembed_probe[/zrl]

[zrl=[baseurl]/help/hook/page_content_top]page_content_top[/zrl]

[zrl=[baseurl]/help/hook/page_end]page_end[/zrl]

[zrl=[baseurl]/help/hook/page_header]page_header[/zrl]

[zrl=[baseurl]/help/hook/parse_atom]parse_atom[/zrl]

[zrl=[baseurl]/help/hook/parse_link]parse_link[/zrl]

[zrl=[baseurl]/help/hook/pdl_selector]pdl_selector[/zrl]

[zrl=[baseurl]/help/hook/perm_is_allowed]perm_is_allowed[/zrl]

[zrl=[baseurl]/help/hook/permissions_create]permissions_create[/zrl]

[zrl=[baseurl]/help/hook/personal_xrd]personal_xrd[/zrl]

[zrl=[baseurl]/help/hook/photo_post_end]photo_post_end[/zrl]

[zrl=[baseurl]/help/hook/photo_upload_begin]photo_upload_begin[/zrl]

[zrl=[baseurl]/help/hook/photo_upload_end]photo_upload_end[/zrl]

[zrl=[baseurl]/help/hook/photo_upload_file]photo_upload_file[/zrl]

[zrl=[baseurl]/help/hook/photo_upload_form]photo_upload_form[/zrl]

[zrl=[baseurl]/help/hook/poke_verbs]poke_verbs[/zrl]

[zrl=[baseurl]/help/hook/post_local]post_local[/zrl]

[zrl=[baseurl]/help/hook/post_local_end]post_local_end[/zrl]

[zrl=[baseurl]/help/hook/post_local_start]post_local_start[/zrl]

[zrl=[baseurl]/help/hook/post_mail]post_mail[/zrl]

[zrl=[baseurl]/help/hook/post_mail_end]post_mail_end[/zrl]

[zrl=[baseurl]/help/hook/post_remote]post_remote[/zrl]

[zrl=[baseurl]/help/hook/post_remote_end]post_remote_end[/zrl]

[zrl=[baseurl]/help/hook/post_remote_update]post_remote_update[/zrl]

[zrl=[baseurl]/help/hook/post_remote_update_end]post_remote_update_end[/zrl]

[zrl=[baseurl]/help/hook/prepare_body]prepare_body[/zrl]

[zrl=[baseurl]/help/hook/prepare_body_final]prepare_body_final[/zrl]

[zrl=[baseurl]/help/hook/prepare_body_init]prepare_body_init[/zrl]

[zrl=[baseurl]/help/hook/probe_well_known]probe_well_known[/zrl]

[zrl=[baseurl]/help/hook/proc_run]proc_run[/zrl]

[zrl=[baseurl]/help/hook/process_channel_sync_delivery]process_channel_sync_delivery[/zrl]

[zrl=[baseurl]/help/hook/profile_advanced]profile_advanced[/zrl]

[zrl=[baseurl]/help/hook/profile_edit]profile_edit[/zrl]

[zrl=[baseurl]/help/hook/profile_photo_content_end]profile_photo_content_end[/zrl]

[zrl=[baseurl]/help/hook/profile_post]profile_post[/zrl]

[zrl=[baseurl]/help/hook/profile_sidebar]profile_sidebar[/zrl]

[zrl=[baseurl]/help/hook/profile_sidebar_enter]profile_sidebar_enter[/zrl]

[zrl=[baseurl]/help/hook/profile_tabs]profile_tabs[/zrl]

[zrl=[baseurl]/help/hook/register_account]register_account[/zrl]

[zrl=[baseurl]/help/hook/render_location]render_location[/zrl]

[zrl=[baseurl]/help/hook/replace_macros]replace_macros[/zrl]

[zrl=[baseurl]/help/hook/reverse_magic_auth]reverse_magic_auth[/zrl]

[zrl=[baseurl]/help/hook/settings_account]settings_account[/zrl]

[zrl=[baseurl]/help/hook/settings_form]settings_form[/zrl]

[zrl=[baseurl]/help/hook/settings_post]settings_post[/zrl]

[zrl=[baseurl]/help/hook/sexpref_selector]sexpref_selector[/zrl]

[zrl=[baseurl]/help/hook/sexpref_selector_min]sexpref_selector_min[/zrl]

[zrl=[baseurl]/help/hook/smilie]smilie[/zrl]

[zrl=[baseurl]/help/hook/smilie]smilie[/zrl]

[zrl=[baseurl]/help/hook/tagged]tagged[/zrl]

[zrl=[baseurl]/help/hook/validate_channelname]validate_channelname[/zrl]
	Used to validate the names used by a channel

[zrl=[baseurl]/help/hook/webfinger]webfinger[/zrl]
	Called when visiting the webfinger (RFC7033) service

[zrl=[baseurl]/help/hook/well_known]well_known[/zrl]
	Called when accessing the '.well-known' special site addresses

[zrl=[baseurl]/help/hook/zid]zid[/zrl]
	Called when adding the observer's zid to a URL

[zrl=[baseurl]/help/hook/zid_init]zid_init[/zrl]
	Called when authenticating a visitor who has used zid

[zrl=[baseurl]/help/hook/zot_finger]zot_finger[/zrl]
	Called when a zot-info packet has been requested (this is our webfinger discovery mechanism)
