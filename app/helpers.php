<?php

if (!function_exists('returnValidationErrorResponse')) {

    function returnValidationErrorResponse($message = '', $data = array())
    {
        $returnArr = [
            'statusCode' => 422,
            'status' => 'vaidation error',
            'message' => $message,
            'data' => ($data) ? ($data) : ((object) $data)
        ];
        return response()->json($returnArr, 422);
    }
}

if (!function_exists('returnErrorResponse')) {

    function returnErrorResponse($message = '', $data = array())
    {
        $returnArr = [
            'statusCode' => 500,
            'status' => 'error',
            'message' => $message,
            'data' => ($data) ? ($data) : ((object) $data)
        ];
        return response()->json($returnArr, 500);
    }
}

if (!function_exists('returnSuccessResponse')) {

    function returnSuccessResponse($message = '', $data = array(), $is_array = false)
    {
        $is_array = !empty($is_array) ? [] : (object)[];
        $returnArr = [
            'statusCode' => 200,
            'status' => 'success',
            'message' => $message,
            'data' => ($data) ? ($data) : $is_array
        ];
        return response()->json($returnArr, 200);
    }
}

if (!function_exists('returnNotFoundResponse')) {

    function returnNotFoundResponse($message = '', $data = array())
    {
        $returnArr = [
            'statusCode' => 404,
            'status' => 'not found',
            'message' => $message,
            'data' => ($data) ? ($data) : ((object) $data)
        ];
        return response()->json($returnArr, 404);
    }
}

if (!function_exists('multiDimToSingleDim')) {

    function multiDimToSingleDim($array = array())
    {
        $mergedArray = [];

        foreach ($array as $item) {
            $decodedItem = json_decode($item, true);

            if (is_array($decodedItem)) {
                $mergedArray = array_merge($mergedArray, $decodedItem);
            }
        }
        return $mergedArray;
    }
}

if (!function_exists('singleArray')) {

    function singleArray($array = array())
    {
        $mergedArray = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                $mergedArray = array_merge($mergedArray, $item);
            }
        }
        return $mergedArray;
    }
}

if (!function_exists('returnNotAllowedResponse')) {

    function returnNotAllowedResponse($message = '', $data = array())
    {
        $returnArr = [
            'statusCode' => 403,
            'status' => 'Not Allowed',
            'message' => $message,
            'data' => ($data) ? ($data) : ((object) $data)
        ];
        return response()->json($returnArr, 403);
    }
}

if (!function_exists('notAuthorizedResponse')) {

    function notAuthorizedResponse($message = '', $data = array(), $status = 'Not Authorized')
    {
        $returnArr = [
            'statusCode' => 400,
            'status' => $status,
            'message' => $message,
            'data' => ($data) ? ($data) : ((object) $data)
        ];
        return response()->json($returnArr, 400);
    }
}
if (!function_exists('getWeeksOfMonth')) {

    function getWeeksOfMonth($start_date, $end_date)
    {
        $weeks = [];

        // Convert dates to DateTime objects
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);

        // Ensure the start date is at least the first day of the month
        $month_start = (clone $start)->modify('first day of this month');
        if ($start < $month_start) {
            $start = $month_start;
        }

        // Ensure the end date is at most the last day of the month
        $month_end = (clone $end)->modify('last day of this month');
        if ($end > $month_end) {
            $end = $month_end;
        }

        // Set the current Sunday to the last Sunday before or on the start date
        $current_sunday = (clone $start)->modify('last sunday');

        // Loop through each week between start and end date
        while ($current_sunday <= $end) {
            $week_start = (clone $current_sunday);
            $week_end = (clone $current_sunday)->modify('+6 days');

            // Ensure the week fits within the start and end boundaries
            if ($week_start < $start) {
                $week_start = clone $start;
            }
            if ($week_end > $end) {
                $week_end = clone $end;
            }

            // Only add valid weeks where start <= end
            if ($week_start <= $week_end) {
                $weeks[] = [
                    'start' => $week_start->format('Y-m-d'),
                    'end'   => $week_end->format('Y-m-d'),
                ];
            }

            // Move to the next Sunday
            $current_sunday->modify('+1 week');
        }

        return $weeks;
    }
}
