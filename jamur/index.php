<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "jamur";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch latest data
$sql = "SELECT temperature, humidity, status_diffuser FROM sensor_readings ORDER BY reading_time DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $temperature = $row['temperature'];
    $humidity = $row['humidity'];
    
    // Determine diffuser status based on humidity
    $diffuser_status = $humidity < 70 ? "Hidup" : "Mati"; 
    $status = $humidity < 70 ? "Kumbung Tidak Normal" : "Kumbung Normal"; 
} else {
    $temperature = "N/A";
    $humidity = "N/A";
    $diffuser_status = "N/A";
    $status = "Data Tidak Tersedia";
}

// Fetch all data for table and graph
$sql_all = "SELECT * FROM sensor_readings ORDER BY id DESC";
$result_all = $conn->query($sql_all);

$reading_times = [];
$temperatures = [];
$humidities = [];
$diffuser_statuses = [];

if ($result_all) {
    while ($row = $result_all->fetch_assoc()) {
        $reading_times[] = $row['reading_time'];
        $temperatures[] = $row['temperature'];
        $humidities[] = $row['humidity'];
        
        // Determine diffuser status based on humidity
        $diffuser_statuses[] = $row['humidity'] < 70 ? "Hidup" : "Mati";
    }
} else {
    die("Error: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Suhu dan Kelembapan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar">
        <h2>Monitoring</h2>
        <ul>
            <li><a href="#home"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="#grafik"><i class="fas fa-chart-line"></i> Grafik</a></li>
            <li><a href="#tabel"><i class="fas fa-table"></i> Tabel</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <section id="home" class="section">
            <div class="section-header">
                <h1>Monitoring Suhu dan Kelembapan</h1>
            </div>
            <div class="cards-container">
                <div class="cards-row">
                    <div class="card temperature">
                        <h2><i class="fas fa-thermometer-half"></i> Temperature</h2>
                        <p id="temperature"><?php echo htmlspecialchars($temperature); ?> °C</p>
                    </div>
                    <div class="card humidity">
                        <h2><i class="fas fa-tint"></i> Humidity</h2>
                        <p id="humidity"><?php echo htmlspecialchars($humidity); ?> %</p>
                    </div>
                </div>
                <div class="cards-row">
                    <div class="card diffuser-status">
                        <h2><i class="fas fa-toggle-on"></i> Status Difuser</h2>
                        <p id="status-difuser"><?php echo htmlspecialchars($diffuser_status); ?></p>
                    </div>
                    <div class="card status">
                        <h2><i class="fas fa-info-circle"></i> Status Kumbung</h2>
                        <p id="status"><?php echo htmlspecialchars($status); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <section id="grafik" class="section">
            <div class="section-header">
                <h1>Grafik</h1>
            </div>
            <canvas id="myChart"></canvas>
        </section>

        <section id="tabel" class="section">
            <div class="section-header">
                <h1>Tabel Data</h1>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Reading_time</th>
                        <th>Temperature (&deg;C)</th>
                        <th>Humidity (%)</th>
                        <th>Status_Difuser</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reading_times as $index => $reading_time): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reading_time); ?></td>
                        <td><?php echo htmlspecialchars($temperatures[$index]); ?></td>
                        <td><?php echo htmlspecialchars($humidities[$index]); ?></td>
                        <td><?php echo htmlspecialchars($diffuser_statuses[$index]); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <script src="script.js"></script>
    <script>
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($reading_times); ?>,
                datasets: [
                    {
                        label: 'Suhu (°C)',
                        data: <?php echo json_encode($temperatures); ?>,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: false
                    },
                    {
                        label: 'Kelembapan (%)',
                        data: <?php echo json_encode($humidities); ?>,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        fill: false
                    }
                ]
            },
            options: {
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Reading Time'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
