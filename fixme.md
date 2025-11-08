BUG: Header does not show admin features when it should

commit 99a282e97042dbbee29b1bbb2a723fde583bd994
Author: Mythic <mythicsorcerer@gmail.com>
Date:   Thu Oct 23 15:29:56 2025 -0700

    moved debug files to debug

[Works]

commit c53023bfdc2d5201b8d4ae418d4588f6824adc04
Merge: 99a282e ba35720
Author: Mythic <mythicsorcerer@gmail.com>
Date:   Thu Oct 23 19:21:52 2025 -0700

    Merge branch 'main' of github.com:MythicSorcerer/xenon-www
    Sigh... Messed with file organization on local and headers on web forgot to push and pull
. my bad.

[Entire header is now gone]

commit 687e2ae7071acfb19353f581cfaa6dbdc8024e58
Author: Mythic <mythicsorcerer@gmail.com>
Date:   Thu Oct 23 22:44:16 2025 -0700

    add backgrounds make sure headers work properly. Everything is fully functional at this p
oint

[Header is back, but admin features are gone. This goes unnoticed as it is untested]

Changes after Oct 23
- Move all .php pages to /name/index.php
- Background is now black to avoid white flashes
- removed /that/
- Fixed 404 incorrect refrence to /error/styles.css instead of /styles.css
- Added dynamic js background
- index.php cloned to /info/ for cleanness


