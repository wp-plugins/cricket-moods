=== Cricket Moods ===
Version: 2.0
Tags: mood, meta
Website: http://dev.wp-plugins.org/wiki/CricketMoods

Cricket Moods is a flexible "mood tag" WordPress plugin.  It allows an author to
add one or more "moods" to every post.  Each mood can be associated with an
image file.  The result would be that the author could have an animated happy
smiley face next to the words I'm Happy! for every post she wishes.

Cricket Moods presents you with a list of available moods when you go to create
or edit a post.  There is no need for you to remember your list of moods.  Using
an option panel in WordPress' administrative menus, you can rename your moods or
even change a mood's graphic without modifying every post that uses that mood.

Despite this plugin's name and my continual reference to "moods", this plugin can be used for more than just moods.  For example, instead of displaying your current mood, you could give your readers the current weather where you are.  You could rename the mood tags to things like "Sunny", "Overcast", and "Raining Cats and Dogs."  You could then upload little cloud and sun images and use those with the tags instead of the pre-defined mood smilies.  You could even leave the tag text or the tag images blank to have either just text or just images.


== Installation ==

1.  Place cricket-moods.php into `/wp-content/plugins`.
2.  Activate the Cricket Moods plugin from the "Plugin Management" panel of
    WordPress.

= Upgrading =

This version of Cricket Moods converts the old 1.0.x DB mood table to the new
wp_option format and deletes the old table automatically. Basically, it's
plug-and-play.


== Usage ==

By default, Cricket Moods will automatically print each post's moods just below
the time for each post.

Using the "Cricket Moods" panel under "Options" in the WordPress administrative
area, you can add, modify, and delete moods as you see fit.  Leaving the "Mood
Name" blank will cause Cricket Moods not to display any text with that mood's
image for a purely pictorial representation of your mood.  Conversely, you can
leave the "Image File" blank and no smilie or other image will be shown with
that mood.  Deleting a mood will also remove any references to that mood from
your blog posts.

= Advanced Usage =

If you want your moods to be displayed somewhere other than the default location, you must place cm_the_moods() somewhere inside The Loop and disable
AutoPrint in the Cricket Moods options.  When called with no parameters, cm_the_moods() only prints the mood image followed by the mood name, an ampersand, and any more moods followed by ampersands.  For example, on a post with the moods "Happy" and "Bored" it will print:
  <img src="/wp-images/smilies/icon_happy.gif" alt="Happy emoticon" /> Happy
    &amp; <img src="/wp-images/smilies/icon_neutral.gif" alt="Bored emoticon" />
    Bored

If there are no moods for the current post, it will print nothing.

cm_the_moods() can take three parameters:
  <?php cm_the_moods('separator', 'before', 'after'); ?>
  'separator'
    (string) Text to place in between multiple moods. Default is ' &amp; '.
  'before'
    (string) Text to place before the first mood. Default is nothing.
  'after'
    (string) Text to place after the last mood. Default is nothing.

A good way to implement this would be:
  <?php cm_the_moods(' and ', '<p>My mood is: ', '.</p>'); ?>

You can also use cm_has_moods() to determine if the current post or a specific
post has moods associated with it.  It will return true or false accordingly.

cm_has_moods() can take one parameter:
  <?php cm_has_moods(post_id); ?>
  post_id
    (integer) The ID of the post you are inquiring about.  Default is the ID of
    the current post.

cm_has_moods() must be used inside The Loop if post_id is not provided.


== Frequently Asked Questions ==

= Can each user have their own list of moods? =

No.  This is not a feature I'm planning.  I can't really see the usefulness of
it.  However, I will gladly accept any contributed code to implement this
feature.

= Is there a limit to the number of moods I can have? =

Not that I know of.  I certainly didn't program one in.


== Screenshots ==

1.  An example of the moods output in action.
2.  A view of the "Write Post" screen with the selectable moods.


== Change Log ==

See changelog.txt


== Copying ==

Copyright (c) 2005 Keith Constable

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
