<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="SokoLink Engine API",
 *     version="1.0.0",
 *     description="The SokoLink Engine API powers the Core Features for the Marketplace activities making a seam less interaction between Sellers, Buyers and Administrators. The documentation comprises the API's for authentication, marketplace , payments , academy, Admin and all the transactions made by the Sellers in the Store.",
 *     @OA\Contact(
 *         name="SokoLink Support Team",
 *         email="support@sokolink.store"
 *     )
 * )
 * @OA\Server(
 *     url="https://api.sokolink.store/api/v1",
 *     description="Live Server"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:2345/api/v1",
 *     description="Test Server"
 * )
 * 
 * 
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Use a valid bearer token",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * ),
 * @OA\Response(
 *     response=401,
 *     description="Unauthorized",
 *     @OA\JsonContent(
 *         @OA\Property(property="status", type="boolean", example=false),
 *         @OA\Property(property="message", type="string", example="Unauthorized"),
 *         @OA\Property(property="code", type="integer", example=401)
 *     )
 * ),
 * @OA\Response(
 *     response=422,
 *     description="Unprocessable Content",
 *     @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
 * ),
 * @OA\Response(
 *     response=500,
 *     description="Unprocessable Content",
 *     @OA\JsonContent(
 *         @OA\Property(property="status", type="boolean", example=false),
 *         @OA\Property(property="message", type="string", example="Error : Something went wrong"),
 *         @OA\Property(property="code", type="integer", example=500),
 *     )
 * ),
 * @OA\Schema(
 *   schema="User",
 *   type="object",
 *   title="User",
 *   description="User model schema",
 *   @OA\Property(property="id", type="integer", example=2),
 *   @OA\Property(property="name", type="string", example="John Doe"),
 *   @OA\Property(property="email", type="string",nullable=true,format="email",  example=null),
 *   @OA\Property(property="phone", type="string", example="+255712345678"),
 *   @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2025-08-21T17:26:14.000000Z"),
 *   @OA\Property(property="phone_verified_at", type="string", format="date-time", nullable=true, example="2025-08-21T17:26:14.000000Z"),
 *   @OA\Property(property="created_by", type="integer", nullable=true, example=null),
 *   @OA\Property(property="deleted_by", type="integer", nullable=true, example=null),
 *   @OA\Property(property="status", type="string", example="active", enum={"active","inactive","suspended","deleted"}),
 *   @OA\Property(
 *       property="archive",
 *       type="object",
 *       nullable=true,
 *       example=null,
 *       description="Archived user details before deletion"
 *   ),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-21T17:21:55.000000Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-21T17:26:14.000000Z")
 * ),
 * @OA\Schema(
 *     schema="Store",
 *     type="object",
 *     title="Store",
 *     required={"id","name","seller_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Tech Store"),
 *     @OA\Property(property="slug", type="string", example="tech-store"),
 *     @OA\Property(property="category_id", type="integer", nullable=true, example=3),
 *     @OA\Property(property="seller_id", type="integer", example=15),
 *     @OA\Property(property="description", type="string", nullable=true, example="We sell electronics and gadgets."),
 *     @OA\Property(property="is_online", type="boolean", example=true),
 *     @OA\Property(property="contact_mobile", type="string", nullable=true, example="+255712345678"),
 *     @OA\Property(property="contact_email", type="string", nullable=true, example="techstore@example.com"),
 *     @OA\Property(property="whatsapp", type="string", nullable=true, example="+255712345678"),
 *     @OA\Property(property="shipping_origin", type="string", nullable=true, example="Dar es Salaam"),
 *     @OA\Property(property="region_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="address", type="string", nullable=true, example="123 Market St, Dar es Salaam"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T10:00:00Z")
 * ),
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     required={"id","name","store_id","price"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="store_id", type="integer", example=5),
 *     @OA\Property(property="name", type="string", example="Laptop X"),
 *     @OA\Property(property="slug", type="string", example="laptop-x"),
 *     @OA\Property(property="description", type="string", example="High-end laptop"),
 *     @OA\Property(property="price", type="number", format="float", example=299.99),
 *     @OA\Property(property="sku", type="string", nullable=true, example="LAP12345"),
 *     @OA\Property(property="barcode", type="string", nullable=true, example="1234567890123"),
 *     @OA\Property(property="is_online", type="boolean", example=true),
 *     @OA\Property(property="stock_qty", type="integer", nullable=true, example=10),
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=3),
 *             @OA\Property(property="name", type="string", example="Electronics")
 *         )
 *     ),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="path", type="string", example="/images/laptop.png"),
 *             @OA\Property(property="is_cover", type="boolean", example=true),
 *             @OA\Property(property="position", type="integer", example=0)
 *         )
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T10:00:00Z")
 * ),
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     title="Review",
 *     required={"user_id","rating","review"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=5, description="ID of the user who wrote the review"),
 *     @OA\Property(property="rating", type="integer", format="int32", example=4, description="Rating value from 1 to 5"),
 *     @OA\Property(property="review", type="string", example="Great product!", description="Text of the review"),
 *     @OA\Property(property="is_verified_purchase", type="boolean", example=true, description="Whether the review is from a verified purchase"),
 *     @OA\Property(property="reviewable_type", type="string", example="App\\Models\\Product", description="Polymorphic type of the reviewable entity"),
 *     @OA\Property(property="reviewable_id", type="integer", example=10, description="ID of the reviewable entity"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         description="The reviewer",
 *         @OA\Property(property="id", type="integer", example=5),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", example="john@example.com")
 *     )
 * ),
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     @OA\Property(
 *         property="status",
 *         type="boolean",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Failed to validate fields"
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="integer",
 *         example=422
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=@OA\Schema(
 *             type="array",
 *             @OA\Items(type="string", example="The field is required.")
 *         ),
 *         example={
 *             "key1": {"some field on the request might be required."},
 *             "key2": {"The key 2 is not well formated."}
 *         }
 *     )
 * ),
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="APIs related to user authentication like login, logout, registration, password updates, etc."
 * ),
 * @OA\Tag(
 *     name="Users",
 *     description="User related APIs, specifically designed for the admin to manage its users."
 * ),
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard APIs for all the users, showing stats and other important data."
 * ),
 * @OA\Tag(
 *     name="Admin",
 *     description="Endpoints for managing administrative actions such as approving/rejecting instructor applications, overseeing users, and handling system-level operations."
 * ),
 * @OA\Tag(
 *     name="Admin Manage Users",
 *     description="Endpoints for managing users, changing user status , editing and deleting."
 * ),
 * @OA\Tag(
 *     name="Payment Options",
 *     description="APIs to manage available payment options for customers, such as pay-now, save-pay-later, etc."
 * ),
 * @OA\Tag(
 *     name="Payment Methods",
 *     description="APIs to manage payment methods like card, bank, and mobile money."
 * ),
 * @OA\Tag(
 *     name="Location",
 *     description="APIs for managing geographic data including regions, cities, and addresses."
 * ),
 * @OA\Tag(
 *     name="Contacts",
 *     description="APIs to manage contact information of users, customers, or business partners."
 * ),
 * @OA\Tag(
 *     name="Academy",
 *     description="APIs related to academy features, including courses, lessons, and instructors."
 * ),
 * @OA\Tag(
 *     name="Categories",
 *     description="APIs to manage categories for stores, products, or any classified data."
 * ),
 * @OA\Tag(
 *     name="Stores",
 *     description="APIs to manage stores, their details, and operational information."
 * ),
 * @OA\Tag(
 *     name="Products",
 *     description="APIs to manage products, including creation, updates, and retrieval of product details."
 * ),
 * @OA\Tag(
 *     name="Orders",
 *     description="APIs to manage customer orders, including order creation, tracking, and history."
 * ),
 * @OA\Tag(
 *     name="Payments",
 *     description="APIs to manage payment processing, payment history, and payment details."
 * ),
 * @OA\Tag(
 *     name="Sales",
 *     description="APIs to manage sales, including recording new sales, editing existing ones, and recording in bulk."
 * ),
 * @OA\Tag(
 *     name="Expense Types",
 *     description="Before Expenses, there is this API Resource for managing all the types of Expenses."
 * ),
 * @OA\Tag(
 *     name="Expenses",
 *     description="This API is for managing all the seller's expenses in their stores. Adding, Editing and Deleting Seller's expenses in a stroe."
 * ),
 * @OA\Tag(
 *     name="Payouts",
 *     description="APIs for handling payouts to users, vendors, or other entities."
 * ),
 * @OA\Tag(
 *     name="Reports",
 *     description="APIs to generate and fetch system reports, analytics, and insights for various entities."
 * ),
 * @OA\Tag(
 *     name="Feedbacks",
 *     description="APIs for sellers to send feedbacks to the admins concerning different situations or suggestions."
 * )
 */


abstract class Controller
{
  //
}
