<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class PageController
 * @package App\Http\Controllers
 */
class PageController extends Controller
{
    public function index()
    {
        try {
            $page = Page::with('createdBy')->latest('id')->get();

            $returnArrData = [
                'pages' => $page,
            ];

            return returnSuccessResponse('Get all device list successfully', $returnArrData);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required',
            'page_type' => 'required|unique:pages',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $pageData = $request->all();
            $page = Page::create($pageData);
            return returnSuccessResponse('Page added successfully', $page->createJsonResponse());
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function update(Request $request)
    {
        $rules = [
            'id' => 'required',
            'title' => 'required',
            'page_type' => 'required|unique:pages,id,' . $request->id,
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $page = Page::findorFail($request->id);
            if (empty($page)) {
                return returnNotFoundResponse('No page found.');
            }
            $page->update($request->all());
            return returnSuccessResponse('Page updated successfully', $page->createJsonResponse());
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function show(Request $request)
    {
        $rules = [
            'page_type' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $page = Page::with('createdBy')->where('page_type', $request->page_type)->first();
            if (empty($page)) {
                return returnNotFoundResponse('No page found.');
            }
            return returnSuccessResponse('Get page detail successfully.', $page);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function destroy(Request $request)
    {
        $rules = [
            'id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $page = Page::findorFail($request->id);
            if (empty($page)) {
                return returnNotFoundResponse('No page found.');
            }
            $page->delete();
            return response()->json(['status' => true, 'message' => 'Page deleted successfully', 'data' => null]);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
