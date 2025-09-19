<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{

    /**
     * @OA\Get(
     *     path="/countries",
     *     tags={"Location"},
     *     summary="Get list of countries",
     *     description="Retrieve a complete list of countries available in the system.",
     *     operationId="getCountries",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of countries retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="List of countries"
     *             ),
     *             @OA\Property(
     *                  property="code",
     *                  type="integer",
     *                  example=200
     *              ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Tanzania"
     *                     ),
     *                     @OA\Property(
     *                         property="abbr",
     *                         type="string",
     *                         example="TZ"
     *                     ),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-09-11T12:30:00.000000Z")
     *                 )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/401"
     *     )
     * )
     */


    public function countries()
    {

        $countries = Country::all();
        return ResponseHelper::success($countries, 'List of countries');
    }



    /**
     * Summary of regionsById
     * Get all regions for a specific country by its ID.
     *
     * @param string $id Country ID
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/countries/{id}/regions",
     *     tags={"Location"},
     *     summary="Get regions by country ID",
     *     description="Returns a list of regions for the given country ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the country",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of regions for a specific country",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Regions by Country ID"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tanzania"),
     *                 @OA\Property(property="abbr", type="string", example="TZ"),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                 @OA\Property(
     *                     property="regions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Arusha"),
     *                         @OA\Property(property="postal_code", type="string", example="23100"),
     *                         @OA\Property(property="country_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Country not found")
     *         )
     *     )
     * )
     */

    public function regionsById(string $id)
    {
        $country = Country::with('regions')->find($id);
        if (!$country) {
            return ResponseHelper::error([], "Country not found.", 404);
        }

        return ResponseHelper::success($country, "Regions by Country ID");
    }




    /**
     * Summary of regionsByCode
     * Get all regions for a specific country by its ISO code.
     *
     * @param string $code Country code (abbr)
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/countries/{code}/regions",
     *     tags={"Location"},
     *     summary="Get regions by country code",
     *     description="Returns a list of regions for the given country code.",
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         description="ISO code of the country (e.g., TZ)",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of regions for a specific country",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Regions by Country Code"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tanzania"),
     *                 @OA\Property(property="abbr", type="string", example="TZ"),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                 @OA\Property(
     *                     property="regions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string", example="Tanzania"),
     *                          @OA\Property(property="abbr", type="string", example="TZ"),
     *                          @OA\Property(property="created_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                          @OA\Property(property="updated_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Country not found")
     *         )
     *     )
     * )
     */

    public function regionsByCode(string $code)
    {

        $country = Country::with('regions')->where('abbr', $code)->get();
        if (!$country) {
            return ResponseHelper::error([], "Country not found.", 404);
        }

        return ResponseHelper::success($country, "Regions by Country Code");
    }




    /**
     * Summary of addCountry
     * Add a new country.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/countries",
     *     tags={"Location"},
     *     summary="Add a new country",
     *     description="Creates a new country record with a name and unique abbreviation.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Tanzania"),
     *             @OA\Property(property="abbr", type="string", example="TZ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Country successfully added",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="New country added."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tanzania"),
     *                 @OA\Property(property="abbr", type="string", example="TZ"),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-11T10:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-09-11T10:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */

    public function addCountry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'abbr' => 'required|string|unique:countries,abbr'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        $validated = $validator->validated();
        $country = Country::create($validated);
        return ResponseHelper::success($country, "New country added.");
    }


    /**
     * Summary of addRegion
     * Add a new region.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/regions",
     *     tags={"Location"},
     *     summary="Add a new region",
     *     description="Creates a new region associated with a country.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Dar es Salaam"),
     *             @OA\Property(property="postal_code", type="string", nullable=true, example="DSM01"),
     *             @OA\Property(property="country_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Region successfully added",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="New regions added."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="name", type="string", example="Dar es Salaam"),
     *                 @OA\Property(property="postal_code", type="string", example="DSM01"),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-11T12:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-09-11T12:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */

    public function addRegion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'postal_code' => 'nullable|string|unique:regions,postal_code',
            'country_id' => 'required|numeric|exists:countries,id'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }


        $validated = $validator->validated();
        $region = Region::create($validated);
        return ResponseHelper::success($region, "New regions added.");


    }



    /**
     * Summary of updateCountry
     * Update an existing country by ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Put(
     *     path="/countries/{id}",
     *     tags={"Location"},
     *     summary="Update a country",
     *     description="Updates an existing country by its ID. Only provided fields will be updated.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the country to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="United Republic of Tanzania"),
     *             @OA\Property(property="abbr", type="string", example="TZ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Country updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="United Republic of Tanzania"),
     *                 @OA\Property(property="abbr", type="string", example="TZ"),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-09-11T12:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Country not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */

    public function updateCountry(Request $request, string $id)
    {
        $country = Country::find($id);

        if (!$country) {
            return ResponseHelper::error([], "Country not found.", 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'abbr' => 'sometimes|required|string|unique:countries,abbr,' . $country->id,
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        $validated = $validator->validated();

        $country->update($validated);

        return ResponseHelper::success($country, "Country updated successfully.");
    }




    /**
     * Summary of updateRegion
     * Update an existing region by ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Put(
     *     path="/regions/{id}",
     *     tags={"Location"},
     *     summary="Update a region",
     *     description="Updates an existing region by its ID. Only provided fields will be updated.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the region to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Dar es Salaam"),
     *             @OA\Property(property="postal_code", type="string", example="DSM001"),
     *             @OA\Property(property="country_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Region updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Region updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="name", type="string", example="Dar es Salaam"),
     *                 @OA\Property(property="postal_code", type="string", example="DSM001"),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-10T04:59:16.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-09-11T12:30:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Region not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Region not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */

    public function updateRegion(Request $request, string $id)
    {
        $region = Region::find($id);

        if (!$region) {
            return ResponseHelper::error([], "Region not found.", 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'postal_code' => 'sometimes|required|string|unique:regions,postal_code,' . $region->id,
            'country_id' => 'sometimes|required|numeric|exists:countries,id'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        $validated = $validator->validated();

        $region->update($validated);

        return ResponseHelper::success($region, "Region updated successfully.");
    }



    /**
     * Delete a country by ID.
     *
     * @param string $id Country ID
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Delete(
     *     path="/countries/{id}",
     *     tags={"Location"},
     *     summary="Delete a country",
     *     description="Deletes a country by its ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the country to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Country deleted successful"),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Country not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Country not found.")
     *         )
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     * )
     */

    public function deleteCountry(string $id)
    {
        $country = Country::find($id);

        if (!$country) {
            return ResponseHelper::error([], "Country not found.", 404);
        }

        $country->delete();
        return ResponseHelper::success([], "Country deleted successful", 200);
    }


    /**
     * Delete a region by ID.
     *
     * @param string $id Region ID
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Delete(
     *     path="/regions/{id}",
     *     tags={"Location"},
     *     summary="Delete a region",
     *     description="Deletes a region by its ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the region to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Region deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Region deleted successful"),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Region not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Region not found.")
     *         ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Unauthroized",
     *       ref="#/components/responses/403"
     *     )
     *     )
     * )
     */

    public function deleteRegion(string $id)
    {
        $region = Region::find($id);

        if (!$region) {
            return ResponseHelper::error([], "Region not found.", 404);
        }

        $region->delete();
        return ResponseHelper::success([], "region deleted successful", 200);
    }
}
