<?php
include 'db_connect.php';

// Get sport_id from URL
$sport_id = isset($_GET['sport_id']) ? (int)$_GET['sport_id'] : 0;

// Initialize variables for sport name and players
$sportName = '';
$players = [];
$completedMatches = [];
$upcomingMatches = [];

// Fetch sport name and players based on sport_id
if ($sport_id > 0) {
    // Fetch sport name
    $sqlSportName = "SELECT Sport_name FROM SPORT WHERE Sport_id = :sport_id";
    $stmt = $pdo->prepare($sqlSportName);
    $stmt->execute(['sport_id' => $sport_id]);
    $sportName = $stmt->fetchColumn();

    // Fetch players for the selected sport along with rankings
    $sqlPlayers = "SELECT pr.Player_name, pr.Region_name, pr.Wins, pr.Losses,
                          RANK() OVER (ORDER BY pr.Wins DESC) AS Ranking
                   FROM PlayerRankings pr
                   WHERE pr.Player_id IN (
                       SELECT Player_id FROM PLAYERS WHERE Sport_id = :sport_id
                   )";
    $stmt = $pdo->prepare($sqlPlayers);
    $stmt->execute(['sport_id' => $sport_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch completed matches
    $sqlCompletedMatches = "SELECT m.Match_id, e.Event_name, gs.Stage_name, p.Player_name, md.Is_winner
                            FROM matchs m
                            JOIN EVENT e ON m.Event_id = e.Event_id
                            JOIN GAME_STAGE gs ON m.Game_stage_id = gs.Stage_id
                            JOIN MATCH_DETAILS md ON m.Match_id = md.Match_id
                            JOIN PLAYERS p ON md.Player_id = p.Player_id
                            WHERE p.Sport_id = :sport_id AND md.Is_winner IS NOT NULL"; // Adjust as needed
    $stmt = $pdo->prepare($sqlCompletedMatches);
    $stmt->execute(['sport_id' => $sport_id]);
    $completedMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch upcoming matches that are not in MATCH_DETAILS
    $sqlUpcomingMatches = "SELECT m.Match_id, e.Event_name, gs.Stage_name
                           FROM matchs m
                           JOIN EVENT e ON m.Event_id = e.Event_id
                           JOIN GAME_STAGE gs ON m.Game_stage_id = gs.Stage_id
                           WHERE m.Match_id NOT IN (
                               SELECT Match_id FROM MATCH_DETAILS
                           ) AND m.Sport_id = :sport_id"; // Ensure correct column reference
    $stmt = $pdo->prepare($sqlUpcomingMatches);
    $stmt->execute(['sport_id' => $sport_id]);
    $upcomingMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sportName = 'Invalid Sport';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sportName) ?> Players</title>
    <link rel="stylesheet" href="assets/style2.css">
    <link rel="icon" href="assets/img/logo1.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
    <div class="scrolling-bar"></div>
    <header>
        <h2 class="logo"><img src="assets/img/logo.png" alt="Website logo"></h2>
        <ul>
            <li><i class="fa-solid fa-house"></i><a href="index.php">Home</a></li>
            <li class="events-dropdown">
                <i class="fa-solid fa-calendar-days"></i>
                <a href="#">Events <i class="fa-solid fa-chevron-down dropdown-arrow"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="?event=1#olympics">Olympics 2024</a></li>
                    <li><a href="?event=2#wimbledon">Wimbledon 2019</a></li>
                </ul>
            </li>
            <li><a href="#about">About</a></li>
            <li><a href="contact.php">Contact-Us</a></li>
        </ul>
    </header>

    <section class="main">
        <div class="text-center">
            <h1 class="banner-heading"><?= htmlspecialchars($sportName) ?> Players</h1>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ranking</th>
                            <th>Player Name</th>
                            <th>Region</th>
                            <th>Wins</th>
                            <th>Losses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($players) > 0): ?>
                            <?php foreach ($players as $player): ?>
                                <tr>
                                    <td class="table_items"><?= htmlspecialchars($player['Ranking']) ?></td>
                                    <td class="table_items"><?= htmlspecialchars($player['Player_name']) ?></td>
                                    <td class="table_items"><?= htmlspecialchars($player['Region_name']) ?></td>
                                    <td class="table_items"><?= htmlspecialchars($player['Wins']) ?></td>
                                    <td class="table_items"><?= htmlspecialchars($player['Losses']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No players found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <!-- Buttons for viewing matches -->
                <div class="match-view-buttons">
                    <button onclick="toggleMatches('upcoming')">View Upcoming Matches</button>
                    <button onclick="toggleMatches('completed')">View Completed Matches</button>
                </div>
                <!-- Section for displaying matches -->
                <div id="matchesDisplay" style="display:none;">
                    <h2>Matches</h2>
                    <div id="upcomingMatches" style="display:none;">
                        <h3>Upcoming Matches</h3>
                        <ul>
                            <?php foreach ($upcomingMatches as $match): ?>
                                <li><?= htmlspecialchars($match['Event_name']) ?> - <?= htmlspecialchars($match['Stage_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div id="completedMatches" style="display:none;">
                        <h3>Completed Matches</h3>
                        <ul>
                            <?php foreach ($completedMatches as $match): ?>
                                <li><?= htmlspecialchars($match['Event_name']) ?> - <?= htmlspecialchars($match['Stage_name']) ?> (<?= htmlspecialchars($match['Player_name'] . ($match['Is_winner'] ? ' - Winner' : ' - Loser')) ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/app2.js"></script>
    <script>
        function toggleMatches(type) {
            const matchesDisplay = document.getElementById('matchesDisplay');
            const upcomingMatches = document.getElementById('upcomingMatches');
            const completedMatches = document.getElementById('completedMatches');

            matchesDisplay.style.display = 'block'; // Show matches section
            matchesDisplay.style.color = '#81DAE3';
            matchesDisplay.style.fontSize = '1.1rem';

            if (type === 'upcoming') {
                upcomingMatches.style.display = 'block';
                upcomingMatches.style.color = 'greenyellow';
                completedMatches.style.display = 'none';
            } else if (type === 'completed') {
                upcomingMatches.style.display = 'none';
                completedMatches.style.color = 'red';
                completedMatches.style.display = 'block';
            }
        }
    </script>
</body>
</html>