<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Details</title>
</head>
<style>
    body {
        font-family: poppins;
    }

    h1 {
        font-size: 35px;
        margin: 27px 0px;
    }

    td {
        font-size: 14px;
    }

    .table tr th,
    .table tr td {
        padding: 10px 15px;
    }

    .table-striped>tbody>tr:nth-of-type(2n+1)>* {
        background-color: #f2f2f2;
        color: #000
    }
</style>

<body>
    <div class="container">
        <h5>Track Details: </br>
            <span style="font-size: 15px;">{{$device_name}}</span> </br>
            <span style="font-size: 15px;">{{$begin_time}} - {{$end_time}}</span>
        </h5>
        <table class="table  table-striped">
            <tr>
                <th>No</th>
                <th style="width: 30%">Position Time</th>
                <th>Speed</th>
                <th>Azimuth</th>
                <th>Position type</th>
                <th>No. of satellites</th>
                <th>Latitude</th>
                <th>Longitude</th>
            </tr>
            @foreach ($deviceData as $key => $data)
            @php
            $positionType = 'N/A';
            if($data['posType'] == 1){
            $positionType = 'GPS';
            }elseif($data['posType'] == 2){
            $positionType = 'LBS';
            }elseif ($data['posType'] == 3) {
            $positionType = 'WIFI';
            }
            @endphp
            <tr>
                <td>{{$key+1}}</td>
                <td>{{ gmdate('Y-m-d H:i:s', strtotime($data['gpsTime'])) }}</td>
                <td>{{ $data['gpsSpeed'] }}</td>
                <td>{{$data['direction']}}</td>
                <td>{{ $positionType }}</td>
                <td>{{ $data['satellite']}}</td>
                <td>{{ $data['lat'] }}</td>
                <td>{{ $data['lng'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</body>

</html>
@php
// dd('ok');
@endphp