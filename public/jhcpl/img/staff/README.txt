DOCTOR / STAFF PHOTO SLOTS
==========================

Drop real clinic photos here as JPG/PNG. The page auto-detects them on
load — no HTML editing needed.

Expected file names (must match exactly):
  dr-robert-james.jpg  — Dr. Robert James (founder, Prof. of Medicine)
  dr-jessie-james.jpg  — Dr. Jessie James (Prof. of Anatomy)

Recommended dimensions: 600x600 px (square), under 200 KB each.
Format: JPG (smaller) or WebP (smallest, better quality).

HOW IT WORKS
------------
Each doctor card on the site has a data-photo attribute pointing at
img/staff/<filename>.jpg. The JS at the bottom of index.html probes
that URL on every page load; if the file exists, it slots the photo
into the card and hides the placeholder icon. If the file is missing,
the card falls back to the generic doctor icon + hint text.

To add a third doctor:
  1. Add a new <div class="doc-card"> ... </div> block in the doctors
     section (copy an existing one).
  2. Set data-photo="img/staff/dr-newname.jpg".
  3. Drop dr-newname.jpg into this folder.
  4. Refresh the page.

CACHE BUSTING
-------------
The JS appends a timestamp query-string to the photo URL so newly-uploaded
photos show up immediately without a hard refresh. Browser caching of
old photos is not an issue.
