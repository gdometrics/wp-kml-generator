Wordpress Simple KML Generator
=========

http://wordpress.org/plugins/simple-kml-generator/

*This plugin does not come with front end map, but it generate location lists for use in Google Map*

##Admin page

###Create KML
![Create KML](http://kingkong123.github.com/wp-kml-generator/create_kml.png)


###KML listing
![View Created Lists](http://kingkong123.github.com/wp-kml-generator/display_lists.png)

=========
##Shortcodes

###Download Link

![Download KML](http://kingkong123.github.com/wp-kml-generator/sc_download_link.png)
```php
[kml_link file="file_name.kml" show_icon="yes|no"]Your Download Text[/kml_link]
```
Parameters:
* file: the kml file name (required)
* show_icon: display the blue KML icon (default "yes")

Content:
* If you want a custom text for the download link, you can add your custom download text (default "Download KML")

*Hint*: if you are not going to change the Content text, you can just input ```[kml_link file="file_name.kml"]```

###Show KML List Items

![List KML Items](http://kingkong123.github.com/wp-kml-generator/sc_kml_list.png)
```php
[kml_list file="file_name.kml" show_title="yes|no" download_link="yes|no"]
```
Parameters:
* file: the kml file name (required)
* show_title: display the list title (default "yes")
* download_link: show the download link (default "yes")

=========
##Widgets

The widgets comes with the same function as the shortcodes. You can edit the widget settings by dragging the widget into the widget area in the admin.

###Download Link Widget

###Show KML List Items Widget

=========
By Kingkong123
