
In Internet explorer jQuery sometimes throws an unidentified exception on draggable events when the getBoundingClientRect() method is called

http://dev.jquery.com/ticket/4996
http://stackoverflow.com/questions/371468/looking-for-a-workaround-for-ie-6-7-unspecified-error-bug-when-accessing-offset
http://www.sencha.com/forum/showthread.php?26276-2.0.1-IE-offsetParent-getBoundingClientRect-after-DOM-replace-issue

jquery-1.4.2-ie-fix.js is an attempt to fix this bug. Seems to work!


On line 5943 in jquery-1.4.2.js the following,

1: var box = elem.getBoundingClientRect()

was changed to,

1: var box = null;
2: try {
3:    box = elem.getBoundingClientRect();
4: } catch(e) {
5:    box = { top : elem.offsetTop, left : elem.offsetLeft }
6: };

Seth