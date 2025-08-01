<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AtmController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\LoansController;
use App\Http\Middleware\BranchMiddleware;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\RemittanceController;
use App\Http\Controllers\DocumentUploadController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\SharesController;
use App\Http\Controllers\ShareProductController;
use App\Http\Controllers\SavingProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BranchRemittanceController;
use App\Http\Controllers\BranchAtmController;
use App\Http\Controllers\SpecialBillingController;
use App\Http\Controllers\BranchSpecialBillingController;
use App\Http\Controllers\FileRetentionController;

//Login
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Profile routes - accessible to both admin and branch users
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/latest', [NotificationController::class, 'getLatestNotifications'])->name('notifications.latest');
    Route::get('/notifications/unread/count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread.count');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');

});

Route::middleware([AdminMiddleware::class])->group(function () {
//Users
Route::get('/users', [LoginController::class, 'userindex'])->name('users');
Route::put('/users/update', [LoginController::class, 'update'])->name('users.update');
Route::delete('/users/destroy', [LoginController::class, 'destroy'])->name('users.destroy');
Route::post('/users/store', [LoginController::class, 'store'])->name('users.store');

//Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/store', [DashboardController::class, 'store'])->name('dashboard.store');

//Billing
Route::get('/Billing', [BillingController::class, 'index'])->name('billing');
Route::get('/billing/export', [BillingController::class, 'export'])->name('billing.export');
Route::get('/billing/loan-report', [BillingController::class, 'exportLoanReport'])->name('billing.loan-report');
Route::get('/billing/members-no-branch', [BillingController::class, 'exportMembersNoBranch'])->name('billing.members-no-branch');
Route::get('/billing/test-period', [BillingController::class, 'testBillingPeriod'])->name('billing.test-period');

// Close Billing Period (manual trigger)
Route::post('/billing/close-period', [BillingController::class, 'closeBillingPeriod'])->name('billing.close-period');

//Billing Export History
Route::get('/billing/exports', [BillingController::class, 'viewExports'])->name('billing.exports');
Route::get('/billing/exports/data', [BillingController::class, 'getExportsData'])->name('billing.exports.data');
Route::get('/billing/exports/{id}/download', [BillingController::class, 'downloadExport'])->name('billing.download-export');

//Billing Member Operations
Route::put('/billing/{member}', [BillingController::class, 'update'])->name('billing.update');
Route::delete('/billing/{member}', [BillingController::class, 'destroy'])->name('billing.destroy');

//Member
Route::get('/Member', [MemberController::class, 'index'])->name('member');
Route::put('/members/{id}', [MemberController::class, 'update'])->name('member.update');
Route::delete('/members/{id}', [MemberController::class, 'destroy'])->name('member.delete');

//Loans
Route::get('/Loans', [LoansController::class, 'index'])->name('loans');
Route::get('/Loans/Members', [LoansController::class, 'list'])->name('list');
Route::post('/loans', [LoansController::class, 'store'])->name('loans.store');
Route::put('/loans/{loan}', [LoansController::class, 'update'])->name('loans.update');
Route::delete('/loans/{loan}', [LoansController::class, 'destroy'])->name('loans.destroy');
Route::get('/Loans/export', [LoansController::class, 'export'])->name('loans.export');
//Document
Route::post('/upload', [DocumentUploadController::class, 'store'])->name('document.upload');
Route::get('/Documents', [DocumentUploadController::class, 'index'])->name('documents');

//File Retention Management
Route::get('/admin/file-retention', [FileRetentionController::class, 'index'])->name('file.retention.dashboard');
Route::get('/admin/file-retention/files', [FileRetentionController::class, 'getFiles'])->name('file.retention.files');
Route::get('/admin/file-retention/download/{type}/{id}', [FileRetentionController::class, 'download'])->name('file.retention.download');
Route::post('/admin/file-retention/cleanup-type', [FileRetentionController::class, 'cleanupType'])->name('file.retention.cleanup.type');
Route::post('/admin/file-retention/cleanup-all', [FileRetentionController::class, 'cleanupAll'])->name('file.retention.cleanup.all');
Route::get('/admin/file-retention/stats', [FileRetentionController::class, 'getStats'])->name('file.retention.stats');
Route::post('/admin/file-retention/preview', [FileRetentionController::class, 'previewCleanup'])->name('file.retention.preview');
Route::post('/admin/file-retention/backup', [FileRetentionController::class, 'createBackup'])->name('file.retention.backup');
Route::get('/admin/file-retention/backup/{filename}/download', [FileRetentionController::class, 'downloadBackup'])->name('file.retention.download.backup');

//atm
Route::get('/atm', [AtmController::class, 'index'])->name('atm');
    Route::post('/atm/update-balance', [AtmController::class, 'updateBalance'])->name('atm.update-balance');
    Route::get('/atm/summary-report', [AtmController::class, 'generateSummaryReport'])->name('atm.summary-report');
    Route::get('/atm/branch-report', [AtmController::class, 'generateBranchReport'])->name('atm.branch-report');
    Route::get('/atm/export-list-of-profile', [AtmController::class, 'exportListOfProfile'])->name('atm.export.list-of-profile');
    Route::get('/atm/export-remittance-report-consolidated', [AtmController::class, 'exportRemittanceReportConsolidated'])->name('atm.export.remittance-report-consolidated');
    Route::get('/atm/export-remittance-report-per-branch', [AtmController::class, 'exportRemittanceReportPerBranch'])->name('atm.export.remittance-report-per-branch');
    Route::get('/atm/export-remittance-report-per-branch-member', [AtmController::class, 'exportRemittanceReportPerBranchMember'])->name('atm.export.remittance-report-per-branch-member');
    Route::post('/atm/post-payment', [AtmController::class, 'postPayment'])->name('atm.post-payment');
    Route::get('/atm/export-posted-payments', [AtmController::class, 'exportPostedPayments'])->name('atm.export-posted-payments');
    Route::get('/atm/export-posted-payments-detailed', [AtmController::class, 'exportPostedPaymentsDetailed'])->name('atm.export-posted-payments-detailed');
Route::get('/atm/generate-batch-report', [AtmController::class, 'generateAtmBatchReport'])->name('atm.generate-batch-report');

//Branch
Route::get('/Branch', [BranchController::class, 'index'])->name('branch');
Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
Route::post('/branches/assign-member', [BranchController::class, 'assignMember'])->name('branches.assignMember');
Route::get('/branches/{id}', [BranchController::class, 'view'])->name('branch.view');
Route::get('/branches/{id}/edit', [BranchController::class, 'edit'])->name('branch.edit');
Route::put('/branches/{id}', [BranchController::class, 'update'])->name('branch.update');
Route::delete('/branches/{id}', [BranchController::class, 'destroy'])->name('branch.destroy');

//Remittance
Route::get('/Remittance', [RemittanceController::class, 'index'])->name('remittance');

//Master List
Route::get('/Master', [MasterController::class, 'index'])->name('master');
Route::put('/master/members/{id}', [MasterController::class, 'update'])->name('master.member.update');
Route::delete('/master/members/{id}', [MasterController::class, 'destroy'])->name('master.member.delete');
Route::post('Admin/master/add', [MasterController::class, 'store'])->name('members.store');
Route::post('/master/upload-coreid', [MasterController::class, 'uploadCoreId'])->name('master.upload.coreid');
Route::post('/master/upload-savings-shares-product', [MasterController::class, 'uploadSavingsSharesProduct'])->name('master.upload.savings-shares-product');
Route::get('/master/export-member-details', [MasterController::class, 'exportMemberDetails'])->name('master.exportMemberDetails');
Route::get('/master/export-members-no-regular-savings', [MasterController::class, 'exportMembersNoRegularSavings'])->name('master.exportMembersNoRegularSavings');

//Savings
Route::get('/savings', [SavingsController::class, 'index'])->name('savings');
Route::post('/savings', [SavingsController::class, 'store'])->name('savings.store');
Route::put('/savings/{id}', [SavingsController::class, 'update'])->name('savings.update');
Route::delete('/savings/{id}', [SavingsController::class, 'destroy'])->name('savings.destroy');
Route::post('/savings/update-deduction-by-product', [SavingsController::class, 'updateDeductionByProduct'])->name('savings.updateDeductionByProduct');
Route::post('/savings/bulk-update-deduction', [SavingsController::class, 'bulkUpdateDeduction'])->name('savings.bulkUpdateDeduction');

//Shares
Route::get('/shares', [SharesController::class, 'index'])->name('shares');
Route::post('/shares', [SharesController::class, 'store'])->name('shares.store');
Route::put('/shares/{id}', [SharesController::class, 'update'])->name('shares.update');
Route::delete('/shares/{id}', [SharesController::class, 'destroy'])->name('shares.destroy');

//Share Products
Route::get('/share-products', [ShareProductController::class, 'index'])->name('share-products.index');
Route::post('/share-products', [ShareProductController::class, 'store'])->name('share-products.store');
Route::put('/share-products/{id}', [ShareProductController::class, 'update'])->name('share-products.update');
Route::delete('/share-products/{id}', [ShareProductController::class, 'destroy'])->name('share-products.destroy');
Route::post('/share-products/{id}/assign-member', [ShareProductController::class, 'assignMember'])->name('share-products.assign-member');

//Saving Products
Route::get('/saving-products', [SavingProductController::class, 'index'])->name('saving-products.index');
Route::post('/saving-products', [SavingProductController::class, 'store'])->name('saving-products.store');
Route::put('/saving-products/{id}', [SavingProductController::class, 'update'])->name('saving-products.update');
Route::delete('/saving-products/{id}', [SavingProductController::class, 'destroy'])->name('saving-products.destroy');
Route::post('/saving-products/{id}/assign-member', [SavingProductController::class, 'assignMember'])->name('saving-products.assign-member');

// Remittance Routes
Route::get('/remittance', [RemittanceController::class, 'index'])->name('remittance.index');
Route::post('/remittance/upload', [RemittanceController::class, 'upload'])->name('remittance.upload');
Route::post('/remittance/upload/share', [RemittanceController::class, 'uploadShare'])->name('remittance.upload.share');
Route::get('/remittance/generate-export', [RemittanceController::class, 'generateExport'])->name('remittance.generateExport');

//special billing

Route::get('/special-billing', [SpecialBillingController::class, 'index'])->name('special-billing.index');
Route::post('/special-billing/import', [SpecialBillingController::class, 'import'])->name('special-billing.import');
Route::get('/special-billing/export', [SpecialBillingController::class, 'export'])->name('special-billing.export');

Route::get('admin/remittance/export-preview', [\App\Http\Controllers\RemittanceController::class, 'exportPreview'])->name('remittance.exportPreview');
Route::get('admin/remittance/export-comparison', [\App\Http\Controllers\RemittanceController::class, 'exportComparison'])->name('remittance.exportComparison');
Route::get('admin/remittance/export-regular-special', [App\Http\Controllers\RemittanceController::class, 'exportRegularSpecial'])->name('remittance.exportRegularSpecial');
Route::get('admin/remittance/export-consolidated', [App\Http\Controllers\RemittanceController::class, 'exportConsolidated'])->name('remittance.exportConsolidated');


Route::get('/admin/contra', [App\Http\Controllers\ContraController::class, 'showAdmin'])->name('admin.contra');
Route::post('/admin/contra', [App\Http\Controllers\ContraController::class, 'storeAdmin']);
Route::get('/admin/contra/accounts', [App\Http\Controllers\ContraController::class, 'getAccountsByType'])->name('admin.contra.accounts');
Route::put('/admin/contra/{id}', [App\Http\Controllers\ContraController::class, 'updateAdmin'])->name('admin.contra.update');
Route::delete('/admin/contra/{id}', [App\Http\Controllers\ContraController::class, 'deleteAdmin'])->name('admin.contra.delete');

});

Route::middleware([BranchMiddleware::class])->group(function () {
//Dashboard
Route::get('/Branch/dashboard', [DashboardController::class, 'index_branch'])->name('dashboard_branch');
Route::post('/Branch/dashboard/store', [DashboardController::class, 'store_branch'])->name('dashboard.store.branch');


//Master

Route::get('/Branch/Master', [MasterController::class, 'index_branch'])->name('master.branch');
Route::put('/Branch/master/members/{id}', [MasterController::class, 'update_branch'])->name('master.member.update.branch');
Route::delete('/Branch/master/members/{id}', [MasterController::class, 'destroy_branch'])->name('master.member.delete.branch');
Route::post('Branch/master/add', [MasterController::class, 'store_branch'])->name('members.store.branch');

//Billing
Route::get('/Branch/Billing', [BillingController::class, 'index_branch'])->name('billing.branch');
Route::get('/Branch/billing/export', [BillingController::class, 'export_branch'])->name('billing.export.branch');
Route::get('/Branch/billing/loan-report', [BillingController::class, 'exportBranchLoanReport'])->name('billing.loan-report.branch');
Route::get('/Branch/billing/exports', [BillingController::class, 'viewExports_branch'])->name('billing.exports.branch');
Route::get('/Branch/billing/exports/{id}/download', [BillingController::class, 'downloadExport_branch'])->name('billing.download-export.branch');
Route::put('/Branch/billing/{member}', [BillingController::class, 'update_branch'])->name('billing.update.branch');
Route::delete('/Branch/billing/{member}', [BillingController::class, 'destroy_branch'])->name('billing.destroy.branch');
Route::post('/Branch/billing/approve', [BillingController::class, 'approve'])->name('billing.approve');
Route::post('/Branch/billing/check-export-status', [BillingController::class, 'checkExportStatus'])->name('billing.check-export-status');
Route::post('/Branch/billing/cancel-approval', [BillingController::class, 'cancelApproval'])->name('billing.cancel-approval');


Route::post('/Branch/upload', [DocumentUploadController::class, 'store_branch'])->name('document.upload.branch');
Route::get('/Branch/Documents', [DocumentUploadController::class, 'index_branch'])->name('documents.branch');

//Savings
Route::get('/Branch/savings', [SavingsController::class, 'index_branch'])->name('savings.branch');

//Shares
Route::get('/Branch/shares', [SharesController::class, 'index_branch'])->name('shares.branch');

// Branch ATM Routes
    Route::get('/branch/atm', [BranchAtmController::class, 'index'])->name('branch.atm');
    Route::post('/branch/atm/update-balance', [BranchAtmController::class, 'updateBalance'])->name('branch.atm.update-balance');
    Route::post('/branch/atm/post-payment', [BranchAtmController::class, 'postPayment'])->name('branch.atm.post-payment');
    Route::get('/branch/atm/export-posted-payments', [BranchAtmController::class, 'exportPostedPayments'])->name('branch.atm.export-posted-payments');
    Route::get('/branch/atm/export-posted-payments-detailed', [BranchAtmController::class, 'exportPostedPaymentsDetailed'])->name('branch.atm.export-posted-payments-detailed');
Route::get('/branch/atm/generate-batch-report', [BranchAtmController::class, 'generateAtmBatchReport'])->name('branch.atm.generate-batch-report');
    Route::get('/branch/atm/export-remittance-report-per-branch', [BranchAtmController::class, 'exportBranchRemittanceReportPerBranch'])->name('branch.atm.export.remittance-report-per-branch');
    Route::get('/branch/atm/export-remittance-report-per-branch-member', [BranchAtmController::class, 'exportBranchRemittanceReportPerBranchMember'])->name('branch.atm.export.remittance-report-per-branch-member');
    Route::get('/branch/atm/export-remittance-report-consolidated', [BranchAtmController::class, 'exportBranchRemittanceReportConsolidated'])->name('branch.atm.export.remittance-report-consolidated');
    Route::get('/branch/atm/export-list-of-profile', [BranchAtmController::class, 'exportBranchListOfProfile'])->name('branch.atm.export.list-of-profile');

// Branch Special Billing
Route::get('/branch/special-billing', [BranchSpecialBillingController::class, 'index'])->name('special-billing.index.branch');
Route::get('/branch/special-billing/export', [BranchSpecialBillingController::class, 'export'])->name('special-billing.export.branch');

// Branch Remittance Routes
Route::get('/branch/remittance', [BranchRemittanceController::class, 'index'])->name('branch.remittance.index');
Route::get('/branch/remittance/generate-export', [BranchRemittanceController::class, 'generateExport'])->name('branch.remittance.generateExport');
Route::get('/branch/remittance/export-regular-special', [App\Http\Controllers\BranchRemittanceController::class, 'exportRegularSpecial'])->name('branchRemittance.exportRegularSpecial');


Route::get('/master/export-member-details-branch', [MasterController::class, 'exportMemberDetailsBranch'])->name('master.exportMemberDetailsBranch');
Route::get('/master/export-members-no-regular-savings-branch', [MasterController::class, 'exportMembersNoRegularSavingsBranch'])->name('master.exportMembersNoRegularSavingsBranch');

// Remove branch contra routes
// Route::get('/branch/contra', [App\Http\Controllers\ContraController::class, 'showBranch'])->name('branch.contra');
// Route::post('/branch/contra', [App\Http\Controllers\ContraController::class, 'storeBranch']);

    // Branch File Retention Management
    Route::get('/Branch/file-retention', [FileRetentionController::class, 'index_branch'])->name('file.retention.dashboard.branch');
    Route::get('/Branch/file-retention/files', [FileRetentionController::class, 'getFiles_branch'])->name('file.retention.files.branch');
    Route::get('/Branch/file-retention/download/{type}/{id}', [FileRetentionController::class, 'download_branch'])->name('file.retention.download.branch');
    Route::post('/Branch/file-retention/cleanup-type', [FileRetentionController::class, 'cleanupType_branch'])->name('file.retention.cleanup.type.branch');
    Route::post('/Branch/file-retention/cleanup-all', [FileRetentionController::class, 'cleanupAll_branch'])->name('file.retention.cleanup.all.branch');
    Route::get('/Branch/file-retention/stats', [FileRetentionController::class, 'getStats_branch'])->name('file.retention.stats.branch');
    Route::post('/Branch/file-retention/preview', [FileRetentionController::class, 'previewCleanup_branch'])->name('file.retention.preview.branch');
    Route::post('/Branch/file-retention/backup', [FileRetentionController::class, 'createBackup_branch'])->name('file.retention.backup.branch');
    Route::get('/Branch/file-retention/backup/{filename}/download', [FileRetentionController::class, 'downloadBackup_branch'])->name('file.retention.download.backup.branch');

});

// Contra routes

Route::get('notifications/export', [App\Http\Controllers\NotificationController::class, 'export'])->name('notifications.export');





