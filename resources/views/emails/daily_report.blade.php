<table cellpadding="0" cellspacing="0"
    style="box-sizing: border-box; font-family: Arial, sans-serif; background-color: rgba(244, 244, 244, 1); margin: 0; padding: 0;width: 600px;">
    <tbody>
        <tr>
            <td align="center" style="box-sizing: border-box; font-family: Arial, sans-serif; ">
                <table cellpadding="0" cellspacing="0"
                    style="background-color: rgba(255, 255, 255, 1); border-radius: 8px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); margin: 0 auto; padding: 0;width: 100%;">
                    <tbody>
                        <tr>
                            <td style="text-align: center; padding: 20px 0;background: #f4f4f4;">
                                <h1 style="color: rgba(76, 175, 80, 1); font-size: 24px; margin: 0">Daily Report</h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 20px 20px 0 20px; font-size: 16px; color: rgba(51, 51, 51, 1)">
                                <p style="margin: 0">Hello, <br> Here is your daily report for the devices:
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 20px; font-size: 16px; color: rgba(51, 51, 51, 1); line-height: 1.6">
                                <ul style="margin: 10px 0 0 20px; padding: 0; color: rgba(51, 51, 51, 1); list-style: disc">
                                    @foreach ($deviceIds as $device)
                                    <li>{{ $device }}</li>
                                    @endforeach
                                </ul>
                                <p style="margin-top: 20px">Please find the attached files below:</p>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="padding: 20px 0; border-top: 1px solid rgba(221, 221, 221, 1); font-size: 14px; color: rgba(119, 119, 119, 1); text-align: center;background: #f4f4f4;">
                                <p style="margin: 0">Thank you for choosing <strong
                                        style="color: rgba(76, 175, 80, 1)">Tanod Tractor</strong>.</p>
                                <p style="margin: 10px 0 0">Best regards,<br>Tanod Tractor Team</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: center; padding: 10px 0;background: #4caf50;color: #fff;">
                                <p style="font-size: 12px; color: #fff !important; margin: 0">© {{date('Y')}} Tanod Tractor. All
                                    rights reserved.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>