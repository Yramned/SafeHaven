# SafeHaven - WITH SENSORS Version

## What's Included

This version includes ALL the fixes and improvements:

✅ **Alerts Page Reorganization**
- CREATE ALERT button at top (for admins)
- 3-column statistics layout (Critical, Warning, Unread)
- No empty space
- Unread alerts fully functional

✅ **Evacuation Request Improvements**
- Fixed dimensions and proportions
- No distortion on any device
- Clean sectioning with 24px spacing

✅ **Sensor Editing Functionality**
- "Edit Sensor Data" button for admins
- Update temperature, humidity, wind speed, flood level
- Change sensor status (Normal, Warning, Critical)
- Real-time updates without page reload
- Reset all sensors to default values

✅ **MVC Pattern Compliant**
- Proper separation of concerns
- Database auto-creates tables

## Use This Version If:

- You want admins to edit sensor data through the UI
- You need real-time sensor monitoring and updates
- You want full control over environmental readings
- You need to simulate different disaster scenarios

## Installation

Same as the NO_SENSORS version:
1. Extract to your web root
2. Import database/database.sql
3. Configure config/database.php if needed
4. Access via browser

Login credentials:
- Admin: admin@safehaven.com / password
- User: user@example.com / password

## Admin Sensor Editing

1. Go to Situational Alerts page
2. Look for "Environmental Sensors" section
3. Click "Edit Sensor Data" button (top right of section)
4. Modal opens with all 4 sensors
5. Update values, units, trends, and status
6. Click "Save" for each sensor
7. Changes appear immediately on dashboard
8. Click "Reset All" to restore default values

## Sensor Data Storage

Sensor data is stored in JSON format in:
`safehaven/storage/sensor_data.json`

This file is automatically created and can be edited manually if needed.
