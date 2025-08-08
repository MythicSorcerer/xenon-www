<?php
    //using the class
    use MCServerStatus\MCPing;
    use MCServerStatus\MCQuery;

    //include composer autoload
    require_once('php/vendor/autoload.php');

    //checking account
    $response=MCPing::check('127.0.0.1');

    //Text treatment
    function convertMinecraftColors($text) {
        $colorCodes = [
            "§0" => "<span style='color:#000000'>", // Black
            "§1" => "<span style='color:#0000AA'>", // Dark Blue
            "§2" => "<span style='color:#00AA00'>", // Dark Green
            "§3" => "<span style='color:#00AAAA'>", // Dark Aqua
            "§4" => "<span style='color:#AA0000'>", // Dark Red
            "§5" => "<span style='color:#AA00AA'>", // Dark Purple
            "§6" => "<span style='color:#FFAA00'>", // Gold
            "§7" => "<span style='color:#AAAAAA'>", // Gray
            "§8" => "<span style='color:#555555'>", // Dark Gray
            "§9" => "<span style='color:#5555FF'>", // Blue
            "§a" => "<span style='color:#55FF55'>", // Green
            "§b" => "<span style='color:#55FFFF'>", // Aqua
            "§c" => "<span style='color:#FF5555'>", // Red
            "§d" => "<span style='color:#FF55FF'>", // Light Purple
            "§e" => "<span style='color:#FFFF55'>", // Yellow
            "§f" => "<span style='color:#FFFFFF'>", // White
            "§l" => "<span style='font-weight:bold'>", // Bold
            "§m" => "<span style='text-decoration:line-through'>", // Strikethrough
            "§n" => "<span style='text-decoration:underline'>", // Underline
            "§o" => "<span style='font-style:italic'>", // Italic
            "§r" => "</span>", // Reset (close span)
            "§k" => "<span class='obfuscated'>", // Obfuscation (handled separately)
        ];
    
        // Replace Minecraft color codes with HTML
        $text = str_replace(array_keys($colorCodes), array_values($colorCodes), $text);
    
        // Close any unclosed <span> tags
        $text .= str_repeat("</span>", substr_count($text, "<span"));
    
        return $text;
    }
    
?>
<!DOCTYPE HTML>
<html>
    <head>
        <title>Mythic Creative</title>
        <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
        <meta charset="utf-8">
            <!-- CSS -->
            <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <!-- TOP BAR ABOVE HEADER -->
    <div class="top-bar">
        <button onclick="location.href='/social/user.html'">User</button>
        <button onclick="location.href='/social/notifications.html'">Notifications</button>
    </div>
    
    <!-- HEADER -->
    <div class="header">
        <h1>Mythic Creative</h1>
        <h3>OP abusing playground</h3>
    </div>
    
    <!-- NAVIGATION -->
    <div class="nav-bar">
        <button onclick="location.href='/info/news.php'">News</button>
        <button onclick="location.href='/info/events.php'">Events</button>
        <button onclick="location.href='/social/forum.php'">Forum</button>
        <button onclick="location.href='/info/FAQ.html'">FAQ</button>
        <button onclick="location.href='/social/support.php'">Support</button>
        <button onclick="location.href='/info/rules.html'">Rules</button>
        <button onclick="location.href='server-status.php'">Server Status</button>
    </div>

    <div class="content">
        <div class="server-info">
            <p>Server IP: <span onclick="copyIP()" style="cursor:pointer; color:blue; text-decoration:underline;">Mythic.ddns.net</span></p>
            <p>(Click to copy)</p>
            <p>Online: <?php echo $response->online ? "Yes" : "No"; ?></p>
            <p>MOTD: <?php echo convertMinecraftColors($response->motd); ?></p>
            <p>Players: <?php echo $response->players; ?></p>
            <p>Max Players: <?php echo $response->max_players; ?></p>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function copyIP(){
            var input = document.createElement('input');
            input.value = "Mythic.ddns.net";
            document.body.appendChild(input);
            input.select();
            document.execCommand("copy");
            document.body.removeChild(input);
            alert("Copied server IP: Mythic.ddns.net to clipboard");
        }
    </script>
</body>
</html>
