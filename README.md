# geeklog2wp
Geeklog 2 Wordpress exporter

This is based on the posting at https://pc.casey.jp/archives/153890244
I updated tested this with geeklog 1.7.4.pl1 and inported it into Wordpress 5.5.3

Your directories may be in a slightly different locations

add the contents of lib-custom.php to geeklog/private/system/lib-custom.php

Create a directory geeklog/private/custom
Copy the conents of phpblock_geeklog2wp.php to geeklog/private/custom/sphpblock_geeklog2wp.php

Create a geeklog block called wp
Title: wp
Block Name: wp
type: PHP Block
Block Function: phpblock_geeklog2mt
Set the permissions so it is only visable to owner, all others should be set to 'no access' this shouldn't make any difference since the connent is only displayed if you are logged in as a user in group root see the line. 

  SEC_inGroup('Root')

The end results will be in geeklog/private/data/gl_stories.txt
