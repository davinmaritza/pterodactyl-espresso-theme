<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;
use Pterodactyl\Http\Middleware\Admin\Servers\ServerInstalled;

Route::get('/', [Admin\BaseController::class, 'index'])->name('admin.index');
Route::get('/quick-search', [Admin\BaseController::class, 'quickSearch'])->name('admin.quick-search');

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/api
|
*/
Route::group(['prefix' => 'api'], function () {
    Route::get('/', [Admin\ApiController::class, 'index'])->name('admin.api.index');
    Route::get('/new', [Admin\ApiController::class, 'create'])->name('admin.api.new');

    Route::post('/new', [Admin\ApiController::class, 'store']);

    Route::delete('/revoke/{identifier}', [Admin\ApiController::class, 'delete'])->name('admin.api.delete');
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/locations
|
*/
Route::group(['prefix' => 'locations'], function () {
    Route::get('/', [Admin\LocationController::class, 'index'])->name('admin.locations');
    Route::get('/view/{location:id}', [Admin\LocationController::class, 'view'])->name('admin.locations.view');

    Route::post('/', [Admin\LocationController::class, 'create']);
    Route::patch('/view/{location:id}', [Admin\LocationController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/databases
|
*/
Route::group(['prefix' => 'databases'], function () {
    Route::get('/', [Admin\DatabaseController::class, 'index'])->name('admin.databases');
    Route::get('/view/{host:id}', [Admin\DatabaseController::class, 'view'])->name('admin.databases.view');

    Route::post('/', [Admin\DatabaseController::class, 'create']);
    Route::patch('/view/{host:id}', [Admin\DatabaseController::class, 'update']);
    Route::delete('/view/{host:id}', [Admin\DatabaseController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Settings Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/settings
|
*/
Route::group(['prefix' => 'settings'], function () {
    Route::get('/integrations', [Admin\Settings\IntegrationsController::class, 'index'])->name('admin.settings.integrations');
    Route::patch('/integrations', [Admin\Settings\IntegrationsController::class, 'update'])->name('admin.settings.integrations.update');
    Route::post('/integrations/test-ai', [Admin\Settings\IntegrationsController::class, 'testAi'])->name('admin.settings.integrations.test_ai');
    Route::post('/integrations/test-discord', [Admin\Settings\IntegrationsController::class, 'testDiscord'])->name('admin.settings.integrations.test_discord');

    Route::get('/links', [Admin\Settings\HubLinksController::class, 'index'])->name('admin.settings.links');
    Route::patch('/links', [Admin\Settings\HubLinksController::class, 'update']);
    Route::get('/', [Admin\Settings\IndexController::class, 'index'])->name('admin.settings');
    Route::get('/mail', [Admin\Settings\MailController::class, 'index'])->name('admin.settings.mail');
    Route::get('/advanced', [Admin\Settings\AdvancedController::class, 'index'])->name('admin.settings.advanced');
    Route::get('/splitter', [Admin\Settings\SplitterController::class, 'index'])->name('admin.settings.splitter');
    Route::get('/cloudbackups', [Admin\Settings\CloudBackupsController::class, 'index'])->name('admin.settings.cloudbackups');
    Route::get('/discordwebhooks', [Admin\Settings\DiscordWebhooksController::class, 'index'])->name('admin.settings.discordwebhooks');

    Route::post('/mail/test', [Admin\Settings\MailController::class, 'test'])->name('admin.settings.mail.test');

    Route::patch('/', [Admin\Settings\IndexController::class, 'update']);
    Route::patch('/mail', [Admin\Settings\MailController::class, 'update']);
    Route::patch('/advanced', [Admin\Settings\AdvancedController::class, 'update']);
    Route::patch('/splitter', [Admin\Settings\SplitterController::class, 'update']);
    Route::post('/splitter/rules', [Admin\Settings\SplitterController::class, 'saveRules'])->name('admin.settings.splitter.rules');
    Route::delete('/splitter/rules/{parent_egg_id}', [Admin\Settings\SplitterController::class, 'deleteRule'])->name('admin.settings.splitter.rules.delete');
    Route::match(['post', 'patch'], '/cloudbackups', [Admin\Settings\CloudBackupsController::class, 'update']);
    Route::get('/cloudbackups/gdrive/auth', [Admin\Settings\CloudBackupsController::class, 'adminAuth'])->name('admin.settings.cloudbackups.gdrive.auth');
    Route::get('/cloudbackups/gdrive/callback', [Admin\Settings\CloudBackupsController::class, 'adminCallback'])->name('admin.settings.cloudbackups.gdrive.callback');
    Route::post('/cloudbackups/gdrive/disconnect', [Admin\Settings\CloudBackupsController::class, 'adminDisconnect'])->name('admin.settings.cloudbackups.gdrive.disconnect');
    Route::patch('/discordwebhooks', [Admin\Settings\DiscordWebhooksController::class, 'update'])->name('admin.settings.discordwebhooks.update');

});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/users
|
*/
Route::group(['prefix' => 'users'], function () {
    Route::get('/', [Admin\UserController::class, 'index'])->name('admin.users');
    Route::get('/accounts.json', [Admin\UserController::class, 'json'])->name('admin.users.json');
    Route::get('/new', [Admin\UserController::class, 'create'])->name('admin.users.new');
    Route::get('/view/{user:id}', [Admin\UserController::class, 'view'])->name('admin.users.view');

    Route::post('/new', [Admin\UserController::class, 'store']);

    Route::patch('/view/{user:id}', [Admin\UserController::class, 'update']);
    Route::delete('/view/{user:id}', [Admin\UserController::class, 'delete'])->name('admin.users.delete');
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/servers
|
*/
Route::group(['prefix' => 'servers'], function () {
    Route::get('/', [Admin\Servers\ServerController::class, 'index'])->name('admin.servers');
    Route::get('/new', [Admin\Servers\CreateServerController::class, 'index'])->name('admin.servers.new');
    Route::get('/view/{server:id}', [Admin\Servers\ServerViewController::class, 'index'])->name('admin.servers.view');

    Route::group(['middleware' => [ServerInstalled::class]], function () {
        Route::get('/view/{server:id}/details', [Admin\Servers\ServerViewController::class, 'details'])->name('admin.servers.view.details');
        Route::get('/view/{server:id}/build', [Admin\Servers\ServerViewController::class, 'build'])->name('admin.servers.view.build');
        Route::get('/view/{server:id}/startup', [Admin\Servers\ServerViewController::class, 'startup'])->name('admin.servers.view.startup');
        Route::get('/view/{server:id}/database', [Admin\Servers\ServerViewController::class, 'database'])->name('admin.servers.view.database');
        Route::get('/view/{server:id}/mounts', [Admin\Servers\ServerViewController::class, 'mounts'])->name('admin.servers.view.mounts');
    });

    Route::get('/view/{server:id}/manage', [Admin\Servers\ServerViewController::class, 'manage'])->name('admin.servers.view.manage');
    Route::get('/view/{server:id}/delete', [Admin\Servers\ServerViewController::class, 'delete'])->name('admin.servers.view.delete');

    Route::post('/new', [Admin\Servers\CreateServerController::class, 'store']);
    Route::post('/view/{server:id}/build', [Admin\ServersController::class, 'updateBuild']);
    Route::post('/view/{server:id}/startup', [Admin\ServersController::class, 'saveStartup']);
    Route::post('/view/{server:id}/database', [Admin\ServersController::class, 'newDatabase']);
    Route::post('/view/{server:id}/mounts', [Admin\ServersController::class, 'addMount'])->name('admin.servers.view.mounts.store');
    Route::post('/view/{server:id}/manage/toggle', [Admin\ServersController::class, 'toggleInstall'])->name('admin.servers.view.manage.toggle');
    Route::post('/view/{server:id}/manage/suspension', [Admin\ServersController::class, 'manageSuspension'])->name('admin.servers.view.manage.suspension');
    Route::post('/view/{server:id}/manage/reinstall', [Admin\ServersController::class, 'reinstallServer'])->name('admin.servers.view.manage.reinstall');
    Route::post('/view/{server:id}/manage/transfer', [Admin\Servers\ServerTransferController::class, 'transfer'])->name('admin.servers.view.manage.transfer');
    Route::post('/view/{server:id}/delete', [Admin\ServersController::class, 'delete']);

    Route::patch('/view/{server:id}/details', [Admin\ServersController::class, 'setDetails']);
    Route::patch('/view/{server:id}/database', [Admin\ServersController::class, 'resetDatabasePassword']);

    Route::delete('/view/{server:id}/database/{database:id}/delete', [Admin\ServersController::class, 'deleteDatabase'])->name('admin.servers.view.database.delete');
    Route::delete('/view/{server:id}/mounts/{mount:id}', [Admin\ServersController::class, 'deleteMount'])
        ->name('admin.servers.view.mounts.delete');
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nodes
|
*/
Route::group(['prefix' => 'nodes'], function () {
    Route::get('/', [Admin\Nodes\NodeController::class, 'index'])->name('admin.nodes');
    Route::get('/splitter', [Admin\Nodes\NodeSplitterController::class, 'index'])->name('admin.nodes.splitter');
    Route::post('/splitter', [Admin\Nodes\NodeSplitterController::class, 'store'])->name('admin.nodes.splitter.store');
    Route::post('/splitter/{node:id}/rename', [Admin\Nodes\NodeSplitterController::class, 'quickRename'])->name('admin.nodes.splitter.rename');
    Route::get('/new', [Admin\NodesController::class, 'create'])->name('admin.nodes.new');
    Route::get('/view/{node:id}', [Admin\Nodes\NodeViewController::class, 'index'])->name('admin.nodes.view');
    Route::get('/view/{node:id}/settings', [Admin\Nodes\NodeViewController::class, 'settings'])->name('admin.nodes.view.settings');
    Route::get('/view/{node:id}/configuration', [Admin\Nodes\NodeViewController::class, 'configuration'])->name('admin.nodes.view.configuration');
    Route::get('/view/{node:id}/allocation', [Admin\Nodes\NodeViewController::class, 'allocations'])->name('admin.nodes.view.allocation');
    Route::get('/view/{node:id}/servers', [Admin\Nodes\NodeViewController::class, 'servers'])->name('admin.nodes.view.servers');
    Route::get('/view/{node:id}/system-information', Admin\Nodes\SystemInformationController::class);

    Route::post('/new', [Admin\NodesController::class, 'store']);
    Route::post('/view/{node:id}/allocation', [Admin\NodesController::class, 'createAllocation']);
    Route::post('/view/{node:id}/allocation/remove', [Admin\NodesController::class, 'allocationRemoveBlock'])->name('admin.nodes.view.allocation.removeBlock');
    Route::post('/view/{node:id}/allocation/alias', [Admin\NodesController::class, 'allocationSetAlias'])->name('admin.nodes.view.allocation.setAlias');
    Route::post('/view/{node:id}/settings/token', Admin\NodeAutoDeployController::class)->name('admin.nodes.view.configuration.token');

    Route::patch('/view/{node:id}/settings', [Admin\NodesController::class, 'updateSettings']);

    Route::delete('/view/{node:id}/delete', [Admin\NodesController::class, 'delete'])->name('admin.nodes.view.delete');
    Route::delete('/view/{node:id}/allocation/remove/{allocation:id}', [Admin\NodesController::class, 'allocationRemoveSingle'])->name('admin.nodes.view.allocation.removeSingle');
    Route::delete('/view/{node:id}/allocations', [Admin\NodesController::class, 'allocationRemoveMultiple'])->name('admin.nodes.view.allocation.removeMultiple');
});

/*
|--------------------------------------------------------------------------
| Mount Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/mounts
|
*/
Route::group(['prefix' => 'mounts'], function () {
    Route::get('/', [Admin\MountController::class, 'index'])->name('admin.mounts');
    Route::get('/view/{mount:id}', [Admin\MountController::class, 'view'])->name('admin.mounts.view');

    Route::post('/', [Admin\MountController::class, 'create']);
    Route::post('/{mount:id}/eggs', [Admin\MountController::class, 'addEggs'])->name('admin.mounts.eggs');
    Route::post('/{mount:id}/nodes', [Admin\MountController::class, 'addNodes'])->name('admin.mounts.nodes');

    Route::patch('/view/{mount:id}', [Admin\MountController::class, 'update']);

    Route::delete('/{mount:id}/eggs/{egg_id}', [Admin\MountController::class, 'deleteEgg']);
    Route::delete('/{mount:id}/nodes/{node_id}', [Admin\MountController::class, 'deleteNode']);
});

/*
|--------------------------------------------------------------------------
| Nest Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nests
|
*/
Route::group(['prefix' => 'nests'], function () {
    Route::get('/', [Admin\Nests\NestController::class, 'index'])->name('admin.nests');
    Route::get('/new', [Admin\Nests\NestController::class, 'create'])->name('admin.nests.new');
    Route::get('/view/{nest:id}', [Admin\Nests\NestController::class, 'view'])->name('admin.nests.view');
    Route::get('/egg/new', [Admin\Nests\EggController::class, 'create'])->name('admin.nests.egg.new');
    Route::get('/egg/{egg:id}', [Admin\Nests\EggController::class, 'view'])->name('admin.nests.egg.view');
    Route::get('/egg/{egg:id}/export', [Admin\Nests\EggShareController::class, 'export'])->name('admin.nests.egg.export');
    Route::get('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'view'])->name('admin.nests.egg.variables');
    Route::get('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'index'])->name('admin.nests.egg.scripts');

    Route::post('/new', [Admin\Nests\NestController::class, 'store']);
    Route::post('/import', [Admin\Nests\EggShareController::class, 'import'])->name('admin.nests.egg.import');
    Route::post('/egg/new', [Admin\Nests\EggController::class, 'store']);
    Route::post('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'store']);

    Route::put('/egg/{egg:id}', [Admin\Nests\EggShareController::class, 'update']);

    Route::patch('/view/{nest:id}', [Admin\Nests\NestController::class, 'update']);
    Route::patch('/egg/{egg:id}', [Admin\Nests\EggController::class, 'update']);
    Route::patch('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'update']);
    Route::patch('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'update'])->name('admin.nests.egg.variables.edit');

    Route::delete('/view/{nest:id}', [Admin\Nests\NestController::class, 'destroy']);
    Route::delete('/egg/{egg:id}', [Admin\Nests\EggController::class, 'destroy']);
    Route::delete('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'destroy']);
});
Route::group(['prefix' => 'announcements'], function () {
    Route::get('/', [\Pterodactyl\Http\Controllers\Admin\AnnouncementController::class, 'index'])->name('admin.announcements');
    Route::post('/', [\Pterodactyl\Http\Controllers\Admin\AnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::post('/{id}/delete', [\Pterodactyl\Http\Controllers\Admin\AnnouncementController::class, 'delete'])->name('admin.announcements.delete');
});

Route::group(['prefix' => 'settings'], function () {
    Route::post('/cloudbackups/node/stop-all', [Admin\Settings\CloudBackupsController::class, 'stopAllBackups']);
    Route::post('/cloudbackups/node/{node}/backup', [\Pterodactyl\Http\Controllers\Admin\Settings\CloudBackupsController::class, 'nodeBackupStart']);
    Route::post('/cloudbackups/node/backup/{backup}/cancel', [\Pterodactyl\Http\Controllers\Admin\Settings\CloudBackupsController::class, 'nodeBackupCancel']);
    Route::post('/cloudbackups/node/{node}/schedule', [\Pterodactyl\Http\Controllers\Admin\Settings\CloudBackupsController::class, 'nodeBackupSchedule']);
    Route::get('/cloudbackups/node/progress', [\Pterodactyl\Http\Controllers\Admin\Settings\CloudBackupsController::class, 'getProgress']);
});

    // ByteNodes Financial Manager Routes
    Route::get('/finance', [Admin\FinancialController::class, 'index'])->name('admin.finance');
    Route::post('/finance/currency', [Admin\FinancialController::class, 'updateCurrency'])->name('admin.finance.currency');
    Route::post('/finance/custom-mrr', [Admin\FinancialController::class, 'updateCustomMrr'])->name('admin.finance.custom_mrr');
    Route::post('/finance/{server}/update', [Admin\FinancialController::class, 'update'])->name('admin.finance.update');
    Route::post('/finance/{server}/toggle', [Admin\FinancialController::class, 'toggleStatus'])->name('admin.finance.toggle');
    Route::post('/finance/{server}/suspend', [Admin\FinancialController::class, 'suspend'])->name('admin.finance.suspend');
    Route::post('/finance/{server}/unsuspend', [Admin\FinancialController::class, 'unsuspend'])->name('admin.finance.unsuspend');
    Route::post('/finance/{server}/mark-paid', [Admin\FinancialController::class, 'markPaid'])->name('admin.finance.mark_paid');


/* SSO Routes */
Route::group(['prefix' => 'sso'], function () {
    Route::get('/', [Pterodactyl\Http\Controllers\Admin\AdminSSOController::class, 'index'])->name('admin.sso.index');
    Route::post('/', [Pterodactyl\Http\Controllers\Admin\AdminSSOController::class, 'generate'])->name('admin.sso.generate');
});

// TicketPro Admin Routes
Route::group(['prefix' => '/extensions/ticketpro'], function () {
    Route::get('/', [\Pterodactyl\Http\Controllers\Admin\Extensions\TicketProAdminController::class, 'index'])->name('admin.extensions.ticketpro.index');
    Route::get('/{ticket}', [\Pterodactyl\Http\Controllers\Admin\Extensions\TicketProAdminController::class, 'show'])->name('admin.extensions.ticketpro.show');
    Route::get('/{ticket}/messages', [\Pterodactyl\Http\Controllers\Admin\Extensions\TicketProAdminController::class, 'messages'])->name('admin.extensions.ticketpro.messages');
    Route::post('/{ticket}/typing', [\Pterodactyl\Http\Controllers\Admin\Extensions\TicketProAdminController::class, 'typing'])->name('admin.extensions.ticketpro.typing');
    Route::post('/{ticket}/reply', [\Pterodactyl\Http\Controllers\Admin\Extensions\TicketProAdminController::class, 'reply'])->name('admin.extensions.ticketpro.reply');
    Route::post('/{ticket}/close', [\Pterodactyl\Http\Controllers\Admin\Extensions\TicketProAdminController::class, 'close'])->name('admin.extensions.ticketpro.close');
    Route::post('/{ticket}/delete', [\Pterodactyl\Http\Controllers\Admin\Extensions\TicketProAdminController::class, 'delete'])->name('admin.extensions.ticketpro.delete');
});
/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/



// Bytenodes TriPay Admin Billing Routes
Route::group(['prefix' => 'billing'], function () {
    Route::get('/', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'index'])->name('admin.billing.index');
    Route::post('/plans', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'storePlan'])->name('admin.billing.plans.store');
    Route::put('/plans/{plan}', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'updatePlan'])->name('admin.billing.plans.update');
    Route::post('/plans/{plan}/stock', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'updateStock'])->name('admin.billing.plans.update_stock');
    Route::delete('/plans/{plan}', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'destroyPlan'])->name('admin.billing.plans.delete');
    Route::post('/settings', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'updateSettings'])->name('admin.billing.settings.update');

    // Paymenter-style Services & Expirations Manager Actions
    Route::post('/services/{server}/extend', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'extendService'])->name('admin.billing.services.extend');
    Route::post('/services/{server}/toggle-suspend', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'toggleSuspendService'])->name('admin.billing.services.toggle_suspend');

    // Paymenter-style Manual Invoicing Actions
    Route::post('/transactions/{transaction}/mark-paid', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'markTransactionPaid'])->name('admin.billing.transactions.mark_paid');
    Route::post('/transactions/{transaction}/cancel', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'cancelTransaction'])->name('admin.billing.transactions.cancel');

    // Admin Wallet Adjustment
    Route::post('/wallet/adjust', [\Pterodactyl\Http\Controllers\Admin\Billing\BillingController::class, 'adjustUserBalance'])->name('admin.billing.wallet.adjust');
});
