<?php

function get_connection(){
    $serverName = "localhost"; // Change to your SQL Server name
    $connectionOptions = array(
        "Database" => "njshukla",
        "Uid" => "sa", // Change if necessary
        "PWD" => "your_password", // Change to your SQL Server password
    );
    
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if(!$conn){
        die(print_r(sqlsrv_errors(), true));
    }
    return $conn;
}

function save_period($day, $time, $event){
    $conn = get_connection();

    $findSql = "SELECT id FROM my_timetable WHERE period=? AND day=?";
    $params = array($time, $day);
    $query = sqlsrv_query($conn, $findSql, $params);

    if($query === false){
        die(print_r(sqlsrv_errors(), true));
    }

    if($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
        $id = $row['id'];
        $updateSql = "UPDATE my_timetable SET event=? WHERE id=?";
        $params = array($event, $id);
        sqlsrv_query($conn, $updateSql, $params);
    } else {
        $sql = "INSERT INTO my_timetable (period, event, day) VALUES (?, ?, ?)";
        $params = array($time, $event, $day);
        sqlsrv_query($conn, $sql, $params);
    }

    sqlsrv_close($conn);
}

function get_all_events(){
    $results = [];
    try {
        $conn = get_connection();
        $query = sqlsrv_query($conn, "SELECT * FROM my_timetable");

        if($query){
            while($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                $results[] = $row;
            }
        }
        sqlsrv_close($conn);
    } catch (Exception $e){
        die(print_r($e->getMessage(), true));
    }
    return $results;
}

function findEvent($events, $day, $time){
    foreach ($events as $event){
        if($event["period"] == $time && $event["day"] == $day){
            return $event["event"];
        }
    }
    return false;
}

$days = ['Time','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$times = ['6:00-6:30AM','6:30-7:00AM','7:00-7:30AM','7:30-8:00AM','8:00-8:30AM','8:30-9:00AM',
  '9:00-9:30AM','9:30-10:00AM','10:00-10:30AM','10:30-11:00AM','11:00-11:30AM',
  '11:30-12:00PM','12:00-12:30PM','12:30-1:00PM','1:00-1:30PM','1:30-2:00PM','2:00-2:30PM'];

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save-event'])){
    $event = $_POST['event'] ?? null;
    $day = $_POST['day'] ?? null;
    $time = $_POST['time'] ?? null;

    if($event && $time && $day){
        save_period($day, $time, $event);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TimeTable</title>
</head>
<style>
  .day-header{
    height: 35px;
    background: #ff8000;
    text-align: center;
    padding: 5px;
    vertical-align: center;
    font-weight: bold;
  }

  .day-block{
    height: 35px;
    padding: 5px;
    background: #f6f6f6;
  }

  .timetable {
    border: 1px solid #3788d8;
    margin-top: 5px;
    border-collapse: collapse;
  }

  .timetable tr, td {
    border: 1px solid #3788d8;
  }
</style>
<body>

<form method="post">
    Event Name: <input name="event" type="text"/><br>
    Day: <select name="day">
        <option>--select--</option>
        <?php foreach($days as $day):?>
            <option><?php echo $day;?></option>
        <?php endforeach;?>
    </select><br>
    Time: <select name="time">
        <option>--select--</option>
        <?php foreach($times as $time):?>
            <option><?php echo $time;?></option>
        <?php endforeach;?>
    </select><br>
    <button type="submit" name="save-event">Save Event</button>
</form>

<table style="width: 100%" class="timetable">
    <tr>
        <?php foreach ($days as $day):?>
            <td class="day-header"><?php echo $day;?></td>
        <?php endforeach;?>
    </tr>

    <?php
    $events = get_all_events();
    $timer=0;
    $total = 0;
    $timeout = count($times) * count($days);

    for($x=0; $x<=count($times)-1; $x++){
        if($total >= $timeout){
            break;
        }
        $time = $times[$timer] ?? null;
        echo "<tr>";

        for ($i=0; $i<=count($days)-1; $i++){
            $day = $days[$i];
            $time = $times[$timer] ?? null;

            if($day == 'Time'){
                echo '<td class="day-block">';
                echo "<span>" . $time . "</span>";
            } else {
                $currentEvent = findEvent($events, $day, $time);
                if($currentEvent){
                    echo '<td class="day-block" style="background:yellowgreen; font-weight: bold ">';
                    echo "<span>".$currentEvent."</span>";
                } else {
                    echo '<td class="day-block">';
                    echo "<span>Free</span>";
                }
            }
            echo '</td>';
            $total++;
        }
        echo "</tr>";
        $timer++;
    }
    ?>
</table>

</body>
</html>
