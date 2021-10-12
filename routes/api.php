<?php

use App\Http\Controllers\BotController;
use App\Http\Controllers\CircleController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\EpochController;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/********************************** V2 ENDPOINTs ***************************************************/

// login
Route::post('/v2/manifest', [ProfileController::class, 'manifest'])->middleware(['verify-sign']);

/************************* TOKEN GATED endpoints *********************************/

Route::prefix('v2')->middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [ProfileController::class, 'logout']);
    Route::get('/full-circle', [CircleController::class, 'fullCircle']);
    Route::get('/v2-token-gifts', [DataController::class, 'newGetGifts']);

    Route::prefix('{circle_id}')->group(function () {

        /************************* ADMIN SIGNATURE REQUIRED ENDPOINTS ****************************/
        Route::prefix('admin')->middleware(['verify-admin-sign'])->group(function () {
            Route::put('/circles/{circle}', [CircleController::class, 'updateCircle']);
            Route::put('/users/{address}', [UserController::class, 'adminUpdateUser']);
            Route::post('/users', [UserController::class, 'createUser']);
            Route::delete('/users/{address}', [UserController::class, 'deleteUser']);
            Route::post('/epoches', [EpochController::class, 'createEpoch']);
            Route::post('/v2/epoches', [EpochController::class, 'newCreateEpoch']);
            Route::put('/epoches/{epoch}', [EpochController::class, 'updateEpoch']);
            Route::delete('/epoches/{epoch}', [EpochController::class, 'deleteEpoch']);
            Route::post('/upload-logo', [CircleController::class, 'uploadCircleLogo']);
            Route::get('/webhook', [CircleController::class, 'getWebhook']);
        });
        /************************* ADMIN SIGNATURE REQUIRED ENDPOINTS ****************************/

        Route::middleware(['verify-sign'])->group(function () {
            Route::put('/users', [UserController::class, 'updateMyUser']);
            Route::put('/users/{address}', [UserController::class, 'updateMyUser']); // deprecated
            Route::post('/v2/token-gifts/{address}', [DataController::class, 'newUpdateGifts']);
            Route::post('/teammates', [DataController::class, 'updateTeammates']);
            Route::post('/nominees', [NominationController::class, 'createNominee']);
            Route::post('/vouch', [NominationController::class, 'addVouch']);
        });

        Route::get('/pending-token-gifts', [DataController::class, 'getPendingGifts']);
        Route::get('/token-gifts', [DataController::class, 'getGifts']);
        Route::get('/csv', [DataController::class, 'generateCsv']);
        Route::get('/nominees', [NominationController::class, 'getNominees']);
        Route::get('/epoches', [EpochController::class, 'epoches']);

    });

    Route::middleware(['verify-sign'])->group(function () {
        Route::post('/upload-avatar/{address}', [ProfileController::class, 'uploadProfileAvatar']);
        Route::post('/upload-background/{address}', [ProfileController::class, 'uploadProfileBackground']);
        Route::post('/profile/{address}', [ProfileController::class, 'saveProfile']);
        Route::post('/profile', [ProfileController::class, 'updateMyProfile']);
        Route::middleware(['hcaptcha-verify'])->group(function () {
            Route::post('/circles', [CircleController::class, 'createCircle']);
        });
    });

    Route::get('/profile/{address}', [ProfileController::class, 'getProfile']);
    Route::get('/circles', [CircleController::class, 'getCircles']);
    Route::get('/protocols', [DataController::class, 'getProtocols']);
    Route::get('/users', [UserController::class, 'getUsers']);
    Route::get('/token-gifts', [DataController::class, 'getGifts']);
    Route::get('/pending-token-gifts', [DataController::class, 'getPendingGifts']);
    Route::get('/active-epochs', [EpochController::class, 'getActiveEpochs']);

});

/************************* TOKEN GATED endpoints *********************************/

/************************* EXTERNAL USED endpoints *********************************/

Route::prefix('{circle_id}')->group(function () {
    Route::get('/token-gifts', [DataController::class, 'getGifts']);
});
/************************* EXTERNAL USED endpoints *********************************/


Route::post("/" . config('telegram.token') . "/bot-update", [BotController::class, 'webHook']);

/********************************** V2 ENDPOINTs ***************************************************/


/********************************** TO BE DEPRECATED ***********************************************/

Route::group([], __DIR__ . '/v1/api.php');

/********************************** TO BE DEPRECATED ***********************************************/

Route::fallback(function () {
    return response()->json(['message' => 'Endpoint Not Found'], 404);
})->name('api.fallback.404');

