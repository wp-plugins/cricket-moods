=== Cricket Moods ===
Stable tag: 3.7.2
Tested up to: 2.7
Requires at least: 2.6
Contributors: kccricket
Donate link: http://kccricket.net/projects/
Tags: mood, meta, post

Cricket Moods is a flexible "mood tag" WordPress plugin.  It allows an author to add one or more "moods" to every post.

== Description ==

Cricket Moods is a flexible "mood tag" WordPress plugin.  It allows an author to
add one or more "moods" to every post.  Each mood can be associated with an
image file.  The result would be that the author could have an animated happy
smiley face next to the words *I'm Happy!* for every post she wishes.

Cricket Moods presents you with a list of available moods when you go to create
or edit a post.  There is no need for you to remember your list of moods.  Using
an option panel in WordPress' administrative menus, you can rename your moods or
even change a mood's graphic without modifying every post that uses that mood.

Despite this plugin's name and my continual references to "moods", this plugin
can be used for more than just moods.  For example, instead of displaying your
current mood, you could give your readers the current weather where you are.
You could rename the mood tags to things like "Sunny", "Overcast", and "Raining
Cats and Dogs."  You could then upload little cloud and sun images and use those
with the tags instead of the pre-defined mood smilies.  You could even leave the
tag text or the tag images blank to have either just text or just images.

*Please note that Cricket Moods will only be receiving critical bug fixes from
this point forth (if we're lucky).  I am working on a complete rewrite of the
plugin called PostBits.  The code for it isn't complete and needs a lot of
work.  If you'd like to help, let me know.  Check it out at:*
http://code.google.com/p/postbits/


== Installation ==

1.  Place `cricket-moods.php` into `/wp-content/plugins`.
2.  Activate the Cricket Moods plugin from the "Plugin Management" panel of
    WordPress.

= Upgrading =

This version of Cricket Moods does not support upgrading from Cricket Moods 1.x.
Upgrades from Cricket Moods 2.x are automagic.


== Usage ==

By default, Cricket Moods will automatically print each post's moods just above
each post's content.  You may also have it automatically print the mood just
below the post content by changing the appropriate option in the Cricket Moods
options panel.

Using the "Moods" panel under "Manage" in the WordPress administrative area, you
can add, modify, and delete moods as you see fit.  Leaving the "Mood Name" blank
will cause Cricket Moods not to display any text with that mood's image for a
purely pictorial representation of your mood.  Conversely, you can leave the
"Image File" blank and no smilie or other image will be shown with that mood.
Deleting a mood will also remove any references to that mood from your blog
posts.

= `cm_the_moods()` =

If you want your moods to be displayed somewhere other than directly above or
below the content, you must place `cm_the_moods()` somewhere inside The Loop and
disable AutoPrint in the Cricket Moods options.  When called with no parameters,
`cm_the_moods()` only prints the mood image followed by the mood name, an
ampersand, and any more moods followed by ampersands.  For example, on a post
with the moods "Happy" and "Bored" it will print:

  `<img src="/wp-images/smilies/icon_happy.gif" alt="Happy emoticon" /> Happy
    &amp; <img src="/wp-images/smilies/icon_neutral.gif" alt="Bored emoticon" />
    Bored`

If there are no moods for the current post, it will print nothing.

`cm_the_moods()` can take three parameters:

	`<?php cm_the_moods('separator', 'before', 'after'); ?>`

* `separator` (string) Text to place in between multiple moods. Default is `' &amp; '`.
* `before` (string) Text to place before the first mood. Default is nothing.
* `after` (string) Text to place after the last mood. Default is nothing.


A good way to implement this would be:

	`<?php cm_the_moods(' and ', '<p>My mood is: ', '.</p>'); ?>`


= `cm_has_moods()` =

You can also use `cm_has_moods()` to determine if the current post or a specific
post has moods associated with it.  It will return true or false accordingly.

`cm_has_moods()` can take one parameter:

	`<?php cm_has_moods(post_id); ?>`

* `post_id` (integer) The ID of the post you are inquiring about.  Default is the ID of the current post.

`cm_has_moods()` must be used inside The Loop if `post_id` is not provided.


== Frequently Asked Questions ==

= Can each user have their own list of moods? =

Yes!  This is a new feature of version 3.0.

= Is there a limit to the number of moods I can have? =

Not that I know of.  I certainly didn't program one in.


== Known Issues ==

* Things get a little fuzzy when you change the author of an existing post with
mood tags.  If the two authors do not have the exact same mood list, the
associated moods may seem to unexpectedly change or not show at all.  It is
recommended that you disassociate all the moods from a post before changing the
post's author.
* Editing the post of another user will cause *that* user's moods to be
displayed, not yours.  This is an unavoidable feature.


== Screenshots ==

1.  An example of a blog post with moods as the reader sees it.
2.  A view of the "Write Post" screen with the selectable moods.
3.  The options panel for administrators.
4.  The Mood management panel.


== Copying ==

Cricket Moods: A flexible mood tag plugin for the WordPress publishing platform.
Copyright (c) 2008 Keith Constable

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
