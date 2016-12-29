[h3] What is Zot?[/h3]
Zot is the revolutionary protocol that powers $Projectname, providing [b]communications[/b], [b]identity management[/b], and [b]access control[/b] across a fully [b]decentralised[/b] network of independent websites, often called "the grid". The resulting platform is a robust system that supports privacy and security while enabling the kind of rich web services typically seen only in centralized, proprietary solutions.

[h4] Communications[/h4]
Communications and social networking are an integral part of the grid. Any channel (and any services provided by that channel) can make full use of feature-rich social communications on a global scale. These communications may be public or private - and private communications comprise not only fully encrypted transport, but also encrypted storage to help protect against accidental snooping and disclosure by rogue system administrators and internet service providers. 

Zot supports a wide array of background services in the grid, from friend suggestions to directory services. New content and data updates are propagated in the background between hubs across the grid according to access control lists and permissions specified by both sender [i]and[/i] receiver channels. Data is also synchronized between an arbitrary number of channel clones, allowing hub members to access data and continue collaborating seamlessly in the event that their primary hub is inaccessible or offline.

[h4] Identity [/h4]
Zot's identity layer is unique. It provides [b]invisible single sign-on[/b] across all sites in the grid. 

It also provides [b]nomadic identity[/b], so that your communications with friends, family, and or anyone else you're communicating with won't be affected by the loss of your primary communication node - either temporarily or permanently. 

The important bits of your identity and relationships can be backed up to a thumb drive, or your laptop, and may appear at any node in the grid at any time - with all your friends and preferences intact. 

Crucially, these nomadic instances are kept in sync so any instance can take over if another one is compromised or damaged. This protects you against not only major system failure, but also temporary site overloads and governmental manipulation or censorship. 

Nomadic identity, single sign-on, and $Projectname's decentralisation of hubs, we believe, introduce a high degree of degree of [b]resiliency[/b] and [b]persistence[/b] in internet communications, that are sorely needed amidst global trends towards corporate centralization, as well as mass and indiscriminate government surveillance and censorship.

As you browse the grid, viewing channels and their unique content, you are seamlessly authenticated as you go, even across completely different server hubs. No passwords to enter. Nothing to type. You're just greeted by name on every new site you visit. 

How does Zot do that? We call it [b]magic-auth[/b], because $Projectname hides the details of the complexities that go into single sign-on logins, and nomadic identities, from the experience of browsing on the grid.  This is one of the design goals of $Projectname: to increase privacy, and freedom on the web, while reducing the complexity and tedium brought by the need to enter new passwords and login names for every different sight that someone might visit online. You login only once on your home hub (or any nomadic backup hub you have chosen). This allows you to access any authenticated services provided anywhere in the grid - such as shopping, blogs, forums, and access to private information. Your password isn't stored on a thousand different sites; it is stored on servers that you control or that you have chosen to trust.

You cannot be silenced. You cannot be removed from the grid unless you yourself choose to exit it.

[h4] Access Control[/h4]
Zot's identity layer allows you to provide fine-grained permissions to any content you wish to publish - and these permissions extend across the grid. This is like having one super huge website made up of an army of small individual websites - and where each channel in the grid can completely control their privacy and sharing preferences for any web resources they create. 

Currently, $Projectname supports access control for many types of data, including post/comment discussion threads, photo albums, events, cloud files, web pages, wikis, and more. Every object and how it is shared and with whom is completely under your control.

This type of control is trivial on large corporate providers because they own the user database. Within the grid, there is no need for a huge user database on your machine - because the grid [b]is[/b] your user database. It has what is essentially infinite capacity (limited by the total number of hubs online across the internet), and is spread amongst hundreds, and potentially millions of computers. 

Access can be granted or denied for any resource, to any channel, or any group of channels; anywhere within the grid. Others can access your content if you permit them to do so, and they do not even need to have an account on your hub. 

[h3]Zot Structures[/h3]
[h4]Zot Signatures[/h4]
All signed data in Zot is accomplished by performing an RSA sign operation using the private key of the initiator. The binary result is then base64url encoded for transport.
[h4]Zot Encryption[/h4]
Encryption is currently provided by AES256-CBC, the Advanced Encryption Standard using 256-bit keys and the Cipher Block Chaining mode of operation. Additional algorithms MAY be supported. A 32-octet key and 16-octet initialisation vector are randomly generated. The desired data is then encrypted using these generated strings and the result base64url encoded. Then we build an array:

[dl terms="b"]
[*= data]The base64url encoded encrypted data
[*= alg]The chosen algorithm, in this case the string 'aes256cbc'.
[*= key]The randomly generated key, RSA encrypted using the recipients public key, and the result base64url encoded
[*= iv]The randomly generated initialization vector, RSA encrypted using the recipient's public key, and the result base64url encoded
[/dl]

[h4]Basic Zot Packet[/h4]
Used for initiating a dialogue with another Zot site. This packet MAY be encrypted. The presence of an array element 'iv' indicates encryption has been applied. When sending an 'auth_check' packet type, this packet MUST be encrypted, using the public key of the destination site (the site key, as opposed to a sender key).  

[code nowrap]
    {
      "type":"notify",
      "sender":{
        "guid":"kgVFf_1_SSbyqH-BNWjWuhAvJ2EhQBTUdw-Q1LwwssAntr8KTBgBSzNVzUm9_RwuDpxI6X8me_QQhZMf7RfjdA",
        "guid_sig":"PT9-TApzpm7QtMxC63MjtdK2nUyxNI0tUoWlOYTFGke3kNdtxSzSvDV4uzq_7SSBtlrNnVMAFx2_1FDgyKawmqVtRPmT7QSXrKOL2oPzL8Hu_nnVVTs_0YOLQJJ0GYACOOK-R5874WuXLEept5-KYg0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q",
        "url":"http:\/\/podunk.edu",
        "url_sig":"T8Bp7j5DHHhQDCFcAHXfuhUfGk2P3inPbImwaXXF1xJd3TGgluoXyyKDx6WDm07x0hqbupoAoZB1qBP3_WfvWiJVAK4N1FD77EOYttUEHZ7L43xy5PCpojJQmkppGbPJc2jnTIc_F1vvGvw5fv8gBWZvPqTdb6LWF6FLrzwesZpi7j2rsioZ3wyUkqb5TDZaNNeWQrIEYXrEnWkRI_qTSOzx0dRTsGO6SpU1fPWuOOYMZG8Nh18nay0kLpxReuHCiCdxjXRVvk5k9rkcMbDBJcBovhiSioPKv_yJxcZVBATw3z3TTE95kGi4wxCEenxwhSpvouwa5b0hT7NS4Ay70QaxoKiLb3ZjhZaUUn4igCyZM0h6fllR5I6J_sAQxiMYD0v5ouIlb0u8YVMni93j3zlqMWdDUZ4WgTI7NNbo8ug9NQDHd92TPmSE1TytPTgya3tsFMzwyq0LZ0b-g-zSXWIES__jKQ7vAtIs9EwlPxqJXEDDniZ2AJ6biXRYgE2Kd6W_nmI7w31igwQTms3ecXe5ENI3ckEPUAq__llNnND7mxp5ZrdXzd5HHU9slXwDShYcW3yDeQLEwAVomTGSFpBrCX8W77n9hF3JClkWaeS4QcZ3xUtsSS81yLrp__ifFfQqx9_Be89WVyIOoF4oydr08EkZ8zwlAsbZLG7eLXY"
      },
      "callback":"\/post",
      "version":1,
      "secret":"1eaa6613699be6ebb2adcefa5379c61a3678aa0df89025470fac871431b70467",
      "secret_sig":"0uShifsvhHnxnPIlDM9lWuZ1hSJTrk3NN9Ds6AKpyNRqf3DUdz81-Xvs8I2kj6y5vfFtm-FPKAqu77XP05r74vGaWbqb1r8zpWC7zxXakVVOHHC4plG6rLINjQzvdSFKCQb5R_xtGsPPfvuE24bv4fvN4ZG2ILvb6X4Dly37WW_HXBqBnUs24mngoTxFaPgNmz1nDQNYQu91-ekX4-BNaovjDx4tP379qIG3-NygHTjFoOMDVUvs-pOPi1kfaoMjmYF2mdZAmVYS2nNLWxbeUymkHXF8lT_iVsJSzyaRFJS1Iqn7zbvwH1iUBjD_pB9EmtNmnUraKrCU9eHES27xTwD-yaaH_GHNc1XwXNbhWJaPFAm35U8ki1Le4WbUVRluFx0qwVqlEF3ieGO84PMidrp51FPm83B_oGt80xpvf6P8Ht5WvVpytjMU8UG7-js8hAzWQeYiK05YTXk-78xg0AO6NoNe_RSRk05zYpF6KlA2yQ_My79rZBv9GFt4kUfIxNjd9OiV1wXdidO7Iaq_Q"
    }
[/code]

[dl terms="b"]
[*= type] The message type: [b]notify, purge, refresh, force_refresh, auth_check, ping[/b] or [b]pickup[/b]. The packet contents vary by message type. Here we will describe the [b]notify[/b] packet. 
[*= callback]A string to be appended onto the url which identifies the Zot communications endpoint on this system. It is typically the string "/post".
[*= version]The Zot protocol identifier, to allow future protocol revisions to co-exist. 
[*= secret]A 64-char string which is randomly generated by the sending site.
[*= secret_sig]The RSA signature of the secret, signed with the sender's private key. 
[*= sender] An array of four components that provide a portable identity. We can contact the URL provided and download a Zot info packet to obtain the public key of the sender, and use that to verify the sender guid and the posting URL signatures.
	[dl terms="b"]
	[*= guid]Typically a 64 character base64url encoded string. This is generated when an identity is created and an attempt is made that it be unique; though this isn't required.
	[*= guid_sig]The RSA signature of the guid, signed by the sender's private key.
	[*= url]The base url of the location this post is originating from.
	[*= url_sig]The RSA signature of url, signed by the sender's private key.
	[/dl]
[/dl]
