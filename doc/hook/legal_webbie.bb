[h2]legal_webbie[/h2]

Called when validating a channel address. By default the valid characters are 
a-z,0-9,-,_, and . Uppercase ASCII characters are folded to lower and any invalid characters are stripped.  

Some federated networks require more restrictive rules.

The hook is called with an array [ 'input' => (supplied text), 'output' => (validated text) ]

A plugin will generally perform a regex filter or text operation on 'input' and provide the results in 'output'. 