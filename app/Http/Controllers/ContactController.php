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

        return ResponseHelper::error($contacts, "Contacts retrieved successfully");
    }



    public function show($id)
    {
        $contact = Contact::where('user_id', Auth::id())->find($id);

        if (!$contact) {
            return ResponseHelper::error([], "Contact not found", 404);
        }

        return ResponseHelper::success($contact, "Contact retrieved");
    }









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

            if($groupId !== null){
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

    // Update contact
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
