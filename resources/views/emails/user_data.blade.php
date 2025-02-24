<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Image UI</title>
    <style>
        body {
            text-align: center;
            background-color: #f3f4f6;
            margin: 0;
            padding-top: 100px;
        }

        .profile-container {
            width: 80px;
            height: 80px;
            margin: 0 auto;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    {{-- Testing image for set to pdf attachment  --}}
    <div class="profile-container">
        <img src="{{ $imageSrc }}" alt="Profile Image" class="profile-image">
    </div>
    <div class="profile-container">
        <img src="{{ $svg }}" alt="pdf file" class="profile-image" />
    </div>

    <div>
        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>User name</th>
            </tr>
            <tr>
                <td>{{ $user['name'] }}</td>
                <td>{{ $user['email'] }}</td>
                <td>{{ $user['phone'] }}</td>
                <td>{{ $user['username'] }}</td>
            </tr>
        </table>
    </div>

</body>

</html>
