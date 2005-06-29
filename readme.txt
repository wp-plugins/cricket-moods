=== Cricket Moods ===
Version: 1.1.0
Tags: mood, meta
Website: http://dev.wp-plugins.org/wiki/CricketMoods

Cricket Moods is a WordPress plugin. It allows an author to add one or more
"moods" to every post. Each mood can be associated with an image file. The
result would be that the author could have an animated happy smiley face next to
the words I'm Happy! for every post she wishes.

The difference between this plugin and the ones I know of that are currently
available is that Cricket Moods presents you with a list of available moods when
you go to create or edit a post. No need to remember your list of moods. The
list of available moods is defined in a table in WordPress's database;
therefore, it is possible to rename the moods or even change a mood's graphic
without modifying every post that uses that mood.


== Installation ==

1.  Place cricket-moods.php into `/wp-content/plugins`.
2.  Add the function `<?php cm_the_moods(); ?>` to your theme (somewhere in The
    Loop) where you want the moods to be displayed.
3.  Activate the Cricket Moods plugin from the Plugin Management panel of
    WordPress.


== Usage ==

In order for this plugin to be useful, you must place cm_the_moods() somewhere
inside The Loop. When called with no parameters, cm_the_moods() only prints the
mood image followed by the mood name, an ampersand, and any more moods followed
by ampersands. For example, on a post with the moods Happy and Bored it will
print:
  `<img src="/wp-images/smilies/icon_happy.gif" alt="Happy emoticon" /> Happy
    &amp; <img src="/wp-images/smilies/icon_neutral.gif" alt="Bored emoticon" />
    Bored`

If there are no moods for the current post, it will print nothing.

cm_the_moods() can take three parameters:
  `<?php cm_the_moods('separator', 'before', 'after'); ?>`
  'separator'
    (string) Text to place in between multiple moods. Default is ' &amp; '.
  'before'
    (string) Text to place before the first mood. Default is nothing.
  'after'
    (string) Text to place after the last mood. Default is nothing.

A good way to implement this would be:
  `<?php cm_the_moods(' and ', '<p>My mood is: ', '.</p>'); ?>`


== Frequently Asked Questions ==

= Can I change the list of moods available? =

As of version 1.1.0, not easily.  If you want to change the list of moods,
you'll have to access the database directly using your favorite tool.  Look for
cricketmoods_moods in the wp_options table.  It's a serialized array, so unless
you know what you're doing, don't bother.


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
