<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\GroupContact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{

    /**
     * @OA\Get(
     *     path="/contacts",
     *     tags={"Contacts"},
     *     summary="Get list of contacts",
     *     description="Retrieve paginated contacts for the authenticated user with optional filters (search, type, group_id).",
     *     operationId="getContacts",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by contact name",
     *         required=false,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by type (customer, client, supplier)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"customer","client","supplier"}, example="customer")
     *     ),
     *     @OA\Parameter(
     *         name="group_id",
     *         in="query",
     *         description="Filter by group ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contacts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contacts retrieved successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=50),
     *                 @OA\Property(property="total", type="integer", example=123),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com"),
     *                         @OA\Property(property="phone", type="string", example="+123456789"),
     *                         @OA\Property(property="type", type="string", example="customer"),
     *                         @OA\Property(property="group_id", type="integer", example=3)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Contact::where('user_id', Auth::id());

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('type') && in_array($request->type, ['customer', 'client', 'supplier'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        $contacts = $query->orderBy('name')->paginate(50);

        return ResponseHelper::success($contacts, "Contacts retrieved successfully");
    }




    /**
     * @OA\Get(
     *     path="/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Get a single contact",
     *     description="Retrieve a contact belonging to the authenticated user by its ID.",
     *     operationId="getContactById",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Contact ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contact retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact retrieved"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+123456789"),
     *                 @OA\Property(property="type", type="string", example="customer"),
     *                 @OA\Property(property="group_id", type="integer", example=3)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Contact not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Contact not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function show($id)
    {
        $contact = Contact::where('user_id', Auth::id())->find($id);

        if (!$contact) {
            return ResponseHelper::error([], "Contact not found", 404);
        }

        return ResponseHelper::success($contact, "Contact retrieved");
    }






    /**
     * @OA\Post(
     *     path="/contacts",
     *     tags={"Contacts"},
     *     summary="Create a new contact",
     *     description="Creates a new contact for the authenticated user. Optionally links the contact to a group and assigns tags.",
     *     operationId="createContact",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","mobile","type"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="mobile", type="string", example="+255712345678"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="whatsapp", type="string", example="+255712345678"),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="string", example="VIP")
     *             ),
     *             @OA\Property(property="type", type="string", enum={"customer","client","supplier"}, example="customer"),
     *             @OA\Property(property="last_interaction_at", type="string", format="date", example="2025-09-13"),
     *             @OA\Property(property="group_id", type="integer", example=2)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Contact created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact created successfully"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="mobile", type="string", example="+255712345678"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="whatsapp", type="string", example="+255712345678"),
     *                 @OA\Property(property="type", type="string", example="customer"),
     *                 @OA\Property(property="last_interaction_at", type="string", format="date", example="2025-09-13"),
     *                 @OA\Property(property="group_id", type="integer", example=2),
     *                 @OA\Property(property="user_id", type="integer", example=5)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'mobile' => 'required|string|regex:/^\+255\d{9}$/',
            'email' => 'nullable|email',
            'whatsapp' => 'nullable|string',
            'tags' => 'nullable|array',
            'type' => 'required|in:customer,client,supplier',
            'last_interaction_at' => 'nullable|date',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();
            $data['user_id'] = Auth::id();
            $groupId = $request->group_id;

            if (isset($data['tags'])) {
                $data['tags'] = json_encode($data['tags']);
            }

            $contact = Contact::create($data);

            if ($groupId !== null) {
                GroupContact::create([
                    'user_id' => Auth::id(),
                    'group_id' => $groupId,
                    'contact_id' => $contact->id,
                ]);
            }

            DB::commit();
            return ResponseHelper::success($contact, "Contact created successfully", 201);

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], $e->getMessage(), 404);
        }
    }



    /**
     * @OA\Put(
     *     path="/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Update an existing contact",
     *     description="Updates a contact by ID for the authenticated user. Only provided fields will be updated.",
     *     operationId="updateContact",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the contact to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="mobile", type="string", example="+255712345678"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="whatsapp", type="string", example="+255712345678"),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="string", example="VIP")
     *             ),
     *             @OA\Property(property="type", type="string", enum={"customer","client","supplier"}, example="customer"),
     *             @OA\Property(property="last_interaction_at", type="string", format="date", example="2025-09-13"),
     *             @OA\Property(property="group_id", type="integer", example=2)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contact updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contact updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="mobile", type="string", example="+255712345678"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="whatsapp", type="string", example="+255712345678"),
     *                 @OA\Property(property="type", type="string", example="customer"),
     *                 @OA\Property(property="last_interaction_at", type="string", format="date", example="2025-09-13"),
     *                 @OA\Property(property="group_id", type="integer", example=2),
     *                 @OA\Property(property="user_id", type="integer", example=5)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Contact not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Contact not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *
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
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return ResponseHelper::error([], "Contact not found", 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'mobile' => 'nullable|string',
            'email' => 'nullable|email',
            'whatsapp' => 'nullable|string',
            'tags' => 'nullable|array',
            'type' => 'nullable|in:customer,client,supplier',
            'last_interaction_at' => 'nullable|date',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        $data = $validator->validated();

        if (isset($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        $contact->update($data);

        return ResponseHelper::success($contact, "Contact updated successfully");
    }





    /**
 * @OA\Delete(
 *     path="/contacts/{id}",
 *     tags={"Contacts"},
 *     summary="Delete a contact",
 *     description="Deletes a contact by ID for the authenticated user.",
 *     operationId="deleteContact",
 *     security={{"sanctum":{}}},
 *
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the contact to delete",
 *         required=true,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Contact deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Contact deleted successfully"),
 *             @OA\Property(property="code", type="integer", example=200),
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Contact not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Contact not found"),
 *             @OA\Property(property="code", type="integer", example=404),
 *         )
 *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
 * )
 */
public function destroy($id)
{
    $contact = Contact::find($id);

    if (!$contact) {
        return ResponseHelper::error([], "Contact not found", 404);
    }

    $contact->delete();

    return ResponseHelper::success([], "Contact deleted successfully");
}

}
