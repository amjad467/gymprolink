<?php

namespace Database\Seeders;

use App\Models\AccessDevice;
use App\Models\CashAccount;
use App\Models\Gym;
use App\Models\GymShop;
use App\Models\InventoryBatch;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\ShopCashShift;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\MembershipService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ١. دروستکردنی مۆڵەتەکان (Permissions)
        $permissions = [
            // هۆڵەکان
            ['name' => 'gyms.manage', 'group_name' => 'gyms', 'ku' => 'بەڕێوەبردنی هۆڵەکان', 'ar' => 'إدارة الصالات', 'en' => 'Manage Gyms'],
            // ئەندامان
            ['name' => 'members.view', 'group_name' => 'members', 'ku' => 'بینینی ئەندامان', 'ar' => 'عرض الأعضاء', 'en' => 'View Members'],
            ['name' => 'members.create', 'group_name' => 'members', 'ku' => 'تۆمارکردنی ئەندام', 'ar' => 'إضافة عضو', 'en' => 'Create Member'],
            ['name' => 'members.update', 'group_name' => 'members', 'ku' => 'دەستکاریکردنی ئەندام', 'ar' => 'تعديل عضو', 'en' => 'Update Member'],
            // بەشدارییەکان
            ['name' => 'memberships.manage', 'group_name' => 'memberships', 'ku' => 'بەڕێوەبردنی بەشدارییەکان', 'ar' => 'إدارة الاشتراكات', 'en' => 'Manage Memberships'],
            ['name' => 'memberships.freeze', 'group_name' => 'memberships', 'ku' => 'ڕاگرتنی بەشداری', 'ar' => 'تجميد الاشتراك', 'en' => 'Freeze Membership'],
            // دارایی
            ['name' => 'payments.create', 'group_name' => 'finance', 'ku' => 'تۆمارکردنی پارەدان', 'ar' => 'تسجيل الدفع', 'en' => 'Record Payment'],
            ['name' => 'payments.refund', 'group_name' => 'finance', 'ku' => 'گەڕاندنەوەی پارە', 'ar' => 'استرجاع المبلغ', 'en' => 'Refund Payment'],
            ['name' => 'expenses.manage', 'group_name' => 'finance', 'ku' => 'بەڕێوەبردنی خەرجییەکان', 'ar' => 'إدارة المصروفات', 'en' => 'Manage Expenses'],
            ['name' => 'reports.view', 'group_name' => 'reports', 'ku' => 'بینینی ڕاپۆرتەکان', 'ar' => 'عرض التقارير', 'en' => 'View Reports'],
            ['name' => 'reports.financial', 'group_name' => 'reports', 'ku' => 'بینینی ڕاپۆرتی دارایی', 'ar' => 'عرض التقارير المالية', 'en' => 'View Financial Reports'],
            // دەرگای زیرەک
            ['name' => 'access.devices.manage', 'group_name' => 'access', 'ku' => 'بەڕێوەبردنی ئامێرەکانی دەرگا', 'ar' => 'إدارة أجهزة الدخول', 'en' => 'Manage Access Devices'],
            ['name' => 'access.logs.view', 'group_name' => 'access', 'ku' => 'بینینی لاگی دەرگا', 'ar' => 'عرض سجلات الدخول', 'en' => 'View Access Logs'],
            // شۆپ و POS
            ['name' => 'shop.inventory.manage', 'group_name' => 'shop', 'ku' => 'بەڕێوەبردنی کۆگای شۆپ', 'ar' => 'إدارة مخزون المتجر', 'en' => 'Manage Shop Inventory'],
            ['name' => 'shop.pos.sell', 'group_name' => 'shop', 'ku' => 'فرۆشتنی کاڵا لە POS', 'ar' => 'البيع في نقطة البيع', 'en' => 'Sell in POS'],
            ['name' => 'shop.refund.approve', 'group_name' => 'shop', 'ku' => 'پەسەندکردنی گەڕاندنەوەی کاڵا', 'ar' => 'الموافقة على الإرجاع', 'en' => 'Approve Shop Refund'],
            ['name' => 'shop.shift.close', 'group_name' => 'shop', 'ku' => 'داخستنی شفتی سندوق', 'ar' => 'إغلاق وردية الصندوق', 'en' => 'Close Cash Shift'],
        ];

        $permModels = [];
        foreach ($permissions as $p) {
            $permModels[$p['name']] = Permission::create([
                'name' => $p['name'],
                'group_name' => $p['group_name'],
                'display_name_ku' => $p['ku'],
                'display_name_ar' => $p['ar'],
                'display_name_en' => $p['en'],
            ]);
        }

        // ٢. دروستکردنی ڕۆڵەکان (Roles)
        $roleSuperAdmin = Role::create([
            'name' => 'super_admin',
            'display_name_ku' => 'بەڕێوەبەری سەرەکی پلاتفۆرم',
            'display_name_ar' => 'مدير النظام العام',
            'display_name_en' => 'Super Administrator',
            'description' => 'دەسەڵاتی تەواو بەسەر هەموو هۆڵەکان و سیستەمەکەدا',
        ]);
        $roleSuperAdmin->permissions()->sync(array_values(array_map(fn($m) => $m->id, $permModels)));

        $roleGymOwner = Role::create([
            'name' => 'gym_owner',
            'display_name_ku' => 'خاوەنی هۆڵ',
            'display_name_ar' => 'مالك الصالة',
            'display_name_en' => 'Gym Owner',
            'description' => 'دەسەڵاتی تەواو بەسەر هۆڵی خۆی و شۆپ و داراییەکەیدا',
        ]);
        $roleGymOwner->permissions()->sync(array_values(array_map(fn($m) => $m->id, array_filter($permModels, fn($k) => $k !== 'gyms.manage', ARRAY_FILTER_USE_KEY))));

        $roleGymManager = Role::create([
            'name' => 'gym_manager',
            'display_name_ku' => 'بەڕێوەبەری هۆڵ',
            'display_name_ar' => 'مدير الصالة',
            'display_name_en' => 'Gym Manager',
            'description' => 'بەڕێوەبردنی ڕۆژانەی ئەندامان، بەشدارییەکان و کارمەندان',
        ]);
        $roleGymManager->permissions()->sync([
            $permModels['members.view']->id, $permModels['members.create']->id, $permModels['members.update']->id,
            $permModels['memberships.manage']->id, $permModels['memberships.freeze']->id,
            $permModels['payments.create']->id, $permModels['expenses.manage']->id,
            $permModels['reports.view']->id, $permModels['access.devices.manage']->id,
            $permModels['access.logs.view']->id, $permModels['shop.pos.sell']->id,
            $permModels['shop.shift.close']->id,
        ]);

        $roleReceptionist = Role::create([
            'name' => 'receptionist',
            'display_name_ku' => 'کارمەندی ڕیسێپشن',
            'display_name_ar' => 'موظف الاستقبال',
            'display_name_en' => 'Receptionist',
            'description' => 'تۆمارکردنی یاریزانان و وەرگرتنی پارەی بەشداری و پشکنینی دەرگا',
        ]);
        $roleReceptionist->permissions()->sync([
            $permModels['members.view']->id, $permModels['members.create']->id, $permModels['members.update']->id,
            $permModels['memberships.manage']->id, $permModels['payments.create']->id,
            $permModels['access.logs.view']->id, $permModels['shop.pos.sell']->id,
        ]);

        $roleAccountant = Role::create([
            'name' => 'accountant',
            'display_name_ku' => 'ژمێریار',
            'display_name_ar' => 'محاسب',
            'display_name_en' => 'Accountant',
            'description' => 'چاودێری دارایی، سندوق، خەرجی و ڕاپۆرتەکان',
        ]);
        $roleAccountant->permissions()->sync([
            $permModels['payments.create']->id, $permModels['payments.refund']->id,
            $permModels['expenses.manage']->id, $permModels['reports.view']->id,
            $permModels['reports.financial']->id,
        ]);

        $roleCashier = Role::create([
            'name' => 'shop_cashier',
            'display_name_ku' => 'کاشێری شۆپ',
            'display_name_ar' => 'أمين صندوق المتجر',
            'display_name_en' => 'Shop Cashier',
            'description' => 'فرۆشتنی تەواوکەرە خۆراکییەکان لە ڕێگەی POS و داخستنی شفت',
        ]);
        $roleCashier->permissions()->sync([
            $permModels['shop.pos.sell']->id,
            $permModels['shop.shift.close']->id,
        ]);

        $roleTrainer = Role::create([
            'name' => 'trainer',
            'display_name_ku' => 'ڕاهێنەر (Trainer)',
            'display_name_ar' => 'مدرب رياضي',
            'display_name_en' => 'Fitness Trainer',
            'description' => 'چاودێری یاریزانان و ئامادەبووان و پلانی ڕاهێنان',
        ]);
        $roleTrainer->permissions()->sync([
            $permModels['members.view']->id,
            $permModels['access.logs.view']->id,
        ]);

        // ٣. دروستکردنی ٣ هۆڵی سەرەکی (The 3 Gyms)
        $gym1 = Gym::create([
            'name' => 'هۆڵی سەرەکی - GymPro Central',
            'code' => 'CENTRAL',
            'phone' => '+9647501112233',
            'email' => 'central@gympro.test',
            'address' => 'هەولێر - شەقامی ٤٠ مەتری',
            'opening_hours' => '06:00 - 00:00',
            'default_credit_limit' => 100000.00,
            'is_active' => true,
        ]);

        $gym2 = Gym::create([
            'name' => 'لقی سۆران - GymPro Soran Branch',
            'code' => 'SORAN',
            'phone' => '+9647502223344',
            'email' => 'soran@gympro.test',
            'address' => 'سۆران - جووتسایدی سەرەکی',
            'opening_hours' => '07:00 - 23:00',
            'default_credit_limit' => 75000.00,
            'is_active' => true,
        ]);

        $gym3 = Gym::create([
            'name' => 'لقی سلێمانی - GymPro Sulaymaniyah Branch',
            'code' => 'SULAIMANI',
            'phone' => '+9647503334455',
            'email' => 'sulaimani@gympro.test',
            'address' => 'سلێمانی - شەقامی بازنەیی مەولەوی',
            'opening_hours' => '06:30 - 23:30',
            'default_credit_limit' => 120000.00,
            'is_active' => true,
        ]);

        // ٤. دروستکردنی بەکارهێنەران
        $superAdmin = User::create([
            'name' => 'کاروان محەمەد (Super Admin)',
            'email' => 'admin@gympro.test',
            'phone' => '+9647500000001',
            'password' => Hash::make('password123'),
            'is_super_admin' => true,
            'is_active' => true,
            'current_gym_id' => $gym1->id,
        ]);
        $superAdmin->roles()->attach($roleSuperAdmin->id);

        $gym1Owner = User::create([
            'name' => 'هێرش کەریم (خاوەنی هۆڵی ناوەندی)',
            'email' => 'owner1@gympro.test',
            'phone' => '+9647501000002',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $gym1->id,
        ]);
        $gym1->users()->attach($gym1Owner->id, ['role_id' => $roleGymOwner->id, 'is_active' => true]);

        $gym1Manager = User::create([
            'name' => 'ئاسۆ عەلی (بەڕێوەبەری هۆڵی ناوەندی)',
            'email' => 'manager1@gympro.test',
            'phone' => '+9647501000003',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $gym1->id,
        ]);
        $gym1->users()->attach($gym1Manager->id, ['role_id' => $roleGymManager->id, 'is_active' => true]);

        $gym1Receptionist = User::create([
            'name' => 'شەنگە تەها (ڕیسێپشن)',
            'email' => 'reception1@gympro.test',
            'phone' => '+9647501000004',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $gym1->id,
        ]);
        $gym1->users()->attach($gym1Receptionist->id, ['role_id' => $roleReceptionist->id, 'is_active' => true]);

        $gym1Cashier = User::create([
            'name' => 'دانا ئەحمەد (کاشێری شۆپ)',
            'email' => 'cashier1@gympro.test',
            'phone' => '+9647501000005',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $gym1->id,
        ]);
        $gym1->users()->attach($gym1Cashier->id, ['role_id' => $roleCashier->id, 'is_active' => true]);

        $gym1Accountant = User::create([
            'name' => 'کامەران ڕەشید (ژمێریار)',
            'email' => 'accountant1@gympro.test',
            'phone' => '+9647501000006',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $gym1->id,
        ]);
        $gym1->users()->attach($gym1Accountant->id, ['role_id' => $roleAccountant->id, 'is_active' => true]);

        $gym1Trainer = User::create([
            'name' => 'سۆران حەمید (ڕاهێنەر)',
            'email' => 'trainer1@gympro.test',
            'phone' => '+9647501000007',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $gym1->id,
        ]);
        $gym1->users()->attach($gym1Trainer->id, ['role_id' => $roleTrainer->id, 'is_active' => true]);

        // بەڕێوەبەری هۆڵی دووەم (سۆران)
        $gym2Manager = User::create([
            'name' => 'ڕێبوار سەعید (بەڕێوەبەری لقی سۆران)',
            'email' => 'manager2@gympro.test',
            'phone' => '+9647502000002',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
            'is_active' => true,
            'current_gym_id' => $gym2->id,
        ]);
        $gym2->users()->attach($gym2Manager->id, ['role_id' => $roleGymManager->id, 'is_active' => true]);

        // ٥. دروستکردنی پلانەکانی بەشداری بۆ هەرسێ هۆڵ
        foreach ([$gym1, $gym2, $gym3] as $gym) {
            MembershipPlan::create([
                'gym_id' => $gym->id,
                'name' => 'پلانی مانگانەی ئاسایی (Monthly Standard)',
                'description' => 'بەشداری تەواو بۆ ماوەی ٣٠ ڕۆژ بەبێ سنووری کاتژمێر',
                'price' => 50000.00,
                'duration_days' => 30,
                'visits_limit' => null,
                'allowed_entry_start' => null,
                'allowed_entry_end' => null,
                'is_active' => true,
            ]);

            MembershipPlan::create([
                'gym_id' => $gym->id,
                'name' => 'پلانی ٣ مانگە بە داشکاندن (Quarterly Special)',
                'description' => 'پلانێکی ئابووری بۆ ماوەی ٩٠ ڕۆژ',
                'price' => 130000.00,
                'duration_days' => 90,
                'visits_limit' => null,
                'is_active' => true,
            ]);

            MembershipPlan::create([
                'gym_id' => $gym->id,
                'name' => 'پلانی ١٢ سەردان (Punch Card 12 Visits)',
                'description' => 'تەنها ١٢ سەردان لە ماوەی ٦٠ ڕۆژدا',
                'price' => 40000.00,
                'duration_days' => 60,
                'visits_limit' => 12,
                'is_active' => true,
            ]);

            MembershipPlan::create([
                'gym_id' => $gym->id,
                'name' => 'پلانی بەیانیان (Morning Special)',
                'description' => 'تەنها لە کاتژمێر ٠٨:٠٠ ی بەیانی تا ٠٢:٠٠ ی دوانیوەڕۆ ڕێگەپێدراوە',
                'price' => 35000.00,
                'duration_days' => 30,
                'allowed_entry_start' => '08:00:00',
                'allowed_entry_end' => '14:00:00',
                'is_active' => true,
            ]);

            // سندوقی پارەی هۆڵ
            CashAccount::create([
                'gym_id' => $gym->id,
                'name' => 'سندوقی سەرەکی هۆڵ',
                'account_type' => 'safe',
                'current_balance' => 500000.00,
                'currency' => 'IQD',
                'is_active' => true,
            ]);

            // ئامێری دەرگای زیرەک
            AccessDevice::create([
                'gym_id' => $gym->id,
                'name' => 'دەرگای سەرەکی - Turnstile Gate 1',
                'device_identifier' => 'GATE_' . $gym->code . '_01',
                'device_type' => 'turnstile_gate',
                'direction' => 'entry',
                'ip_address' => '192.168.1.50',
                'port' => 8080,
                'api_key' => 'api_key_' . strtolower($gym->code) . '_01',
                'secret_token' => 'sec_token_' . strtolower($gym->code) . '_01',
                'relay_pulse_ms' => 3000,
                'is_active' => true,
            ]);
        }

        // ٦. دروستکردنی نموونەی یاریزانان و دروستکردنی کۆدی QR
        $accessService = app(AccessControlService::class);
        $membershipService = app(MembershipService::class);
        $planMonthlyGym1 = MembershipPlan::where('gym_id', $gym1->id)->where('duration_days', 30)->first();

        // یاریزانی ١ لە هۆڵی ١ (چالاک بە بەشداری کارا)
        $member1 = $membershipService->registerMember([
            'full_name' => 'سەروەر هۆشیار',
            'phone' => '+9647501234567',
            'gender' => 'male',
            'birth_date' => '1995-05-15',
            'allow_credit' => true,
            'credit_limit' => 150000.00,
        ], $gym1->id);

        $membershipService->subscribeToPlan(
            $member1,
            $planMonthlyGym1,
            $gym1Receptionist->id,
            discount: 0,
            initialPayment: 50000.00,
            paymentMethod: 'cash'
        );

        // یاریزانی ٢ لە هۆڵی ١ (بەشداری بەسەرچوو بۆ تاقیکردنەوەی دەرگا)
        $member2 = $membershipService->registerMember([
            'full_name' => 'بەختیار عوسمان',
            'phone' => '+9647507654321',
            'gender' => 'male',
        ], $gym1->id);

        $expiredMembership = Membership::create([
            'gym_id' => $gym1->id,
            'member_id' => $member2->id,
            'membership_plan_id' => $planMonthlyGym1->id,
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(10)->toDateString(),
            'original_end_date' => now()->subDays(10)->toDateString(),
            'original_price' => 50000.00,
            'paid_price' => 50000.00,
            'status' => 'expired',
            'created_by_user_id' => $gym1Receptionist->id,
        ]);
        $member2->update(['status' => 'expired']);

        // یاریزانی ٣ لە هۆڵی ١ (ڕاگیراو - Frozen)
        $member3 = $membershipService->registerMember([
            'full_name' => 'ئاراس ئەحمەد',
            'phone' => '+9647508889900',
            'gender' => 'male',
        ], $gym1->id);

        $mem3 = $membershipService->subscribeToPlan(
            $member3,
            $planMonthlyGym1,
            $gym1Receptionist->id,
            discount: 0,
            initialPayment: 50000.00
        );
        $membershipService->freezeMembership($mem3->id, 15, 'نەشتەرگەری و پشووی پزیشکی', $gym1Manager->id);

        // یاریزانێک لە هۆڵی ٢ (لقی سۆران) بۆ تاقیکردنەوەی ڕەتکردنەوەی چوونەژوورەوەی هۆڵی ١
        $planMonthlyGym2 = MembershipPlan::where('gym_id', $gym2->id)->first();
        $memberGym2 = $membershipService->registerMember([
            'full_name' => 'کاوە سۆران',
            'phone' => '+9647509991122',
            'gender' => 'male',
        ], $gym2->id);
        $membershipService->subscribeToPlan($memberGym2, $planMonthlyGym2, $gym2Manager->id, 0, 50000.00);

        // ٧. دروستکردنی شۆپی تەواوکەری خۆراکی لە هەر سێ هۆڵ
        $shop1 = GymShop::create([
            'gym_id' => $gym1->id,
            'name' => 'شۆپی تەواوکەری هۆڵی ناوەندی (GymPro Supplements Central)',
            'code' => 'SHOP_CENTRAL',
            'location_details' => 'نهۆمی دووەم - تەنیشت ڕیسێپشن',
            'is_active' => true,
        ]);

        $shop2 = GymShop::create([
            'gym_id' => $gym2->id,
            'name' => 'شۆپی تەواوکەری لقی سۆران (GymPro Supplements Soran)',
            'code' => 'SHOP_SORAN',
            'location_details' => 'نزیک دەرگای سەرەکی',
            'is_active' => true,
        ]);

        $shop3 = GymShop::create([
            'gym_id' => $gym3->id,
            'name' => 'شۆپی تەواوکەری لقی سلێمانی (GymPro Supplements Sulaimani)',
            'code' => 'SHOP_SULAIMANI',
            'location_details' => 'نهۆمی یەکەم',
            'is_active' => true,
        ]);

        $catProtein = ProductCategory::firstOrCreate(['name_ku' => 'پرۆتین'], [
            'name_ku' => 'پرۆتین',
            'name_ar' => 'بروتين',
            'name_en' => 'Protein',
        ]);
        $catCreatine = ProductCategory::firstOrCreate(['name_ku' => 'کریاتین'], [
            'name_ku' => 'کریاتین',
            'name_ar' => 'كرياتين',
            'name_en' => 'Creatine',
        ]);
        $catPreWorkout = ProductCategory::firstOrCreate(['name_ku' => 'پێش وەرزش (Pre-Workout)'], [
            'name_ku' => 'پێش وەرزش (Pre-Workout)',
            'name_ar' => 'طاقة قبل التمرين',
            'name_en' => 'Pre-Workout',
        ]);
        $catAmino = ProductCategory::firstOrCreate(['name_ku' => 'ترشە ئەمینییەکان (BCAA)'], [
            'name_ku' => 'ترشە ئەمینییەکان (BCAA)',
            'name_ar' => 'أحماض أمينية',
            'name_en' => 'BCAA & Amino',
        ]);
        $catVitamins = ProductCategory::firstOrCreate(['name_ku' => 'ڤیتامین و کانزاکان'], [
            'name_ku' => 'ڤیتامین و کانزاکان',
            'name_ar' => 'فيتامينات ومعادن',
            'name_en' => 'Vitamins & Minerals',
        ]);

        $brandON = ProductBrand::firstOrCreate(['name' => 'Optimum Nutrition (ON)'], [
            'name' => 'Optimum Nutrition (ON)',
            'country_of_origin' => 'ئەمریکا (USA)',
        ]);
        $brandDymatize = ProductBrand::firstOrCreate(['name' => 'Dymatize Nutrition'], [
            'name' => 'Dymatize Nutrition',
            'country_of_origin' => 'ئەمریکا (USA)',
        ]);
        $brandMuscleTech = ProductBrand::firstOrCreate(['name' => 'MuscleTech'], [
            'name' => 'MuscleTech',
            'country_of_origin' => 'کەنەدا (Canada)',
        ]);
        $brandApplied = ProductBrand::firstOrCreate(['name' => 'Applied Nutrition'], [
            'name' => 'Applied Nutrition',
            'country_of_origin' => 'بەریتانیا (UK)',
        ]);
        $brandKevin = ProductBrand::firstOrCreate(['name' => 'Kevin Levrone Signature'], [
            'name' => 'Kevin Levrone Signature',
            'country_of_origin' => 'پۆڵەندا (EU)',
        ]);

        $productWhey = Product::create([
            'category_id' => $catProtein->id,
            'brand_id' => $brandON->id,
            'name_ku' => 'گۆڵد ستاندارد وەی پرۆتین',
            'name_ar' => 'واي بروتين جولد ستاندرد',
            'name_en' => 'Gold Standard 100% Whey',
            'description' => 'تەواوکەری پرۆتینی گۆڵد ستاندارد خاوەنی ٢٤ گرام پرۆتینە لە هەر سکووپێکدا',
            'ingredients' => 'Whey Protein Isolate, Whey Protein Concentrate, BCAAs',
            'is_active' => true,
        ]);

        // جۆری شوکۆلاتە 5 lbs
        $varChoc5lb = ProductVariant::create([
            'product_id' => $productWhey->id,
            'sku' => 'ON-WHEY-CHOC-5LB',
            'barcode' => '748927028669',
            'size' => '5 lbs (2.27 kg)',
            'flavor' => 'Double Rich Chocolate',
            'default_cost_price' => 60000.00,
            'default_sale_price' => 85000.00,
            'member_sale_price' => 80000.00,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        // جۆری ڤانیلا 5 lbs
        $varVan5lb = ProductVariant::create([
            'product_id' => $productWhey->id,
            'sku' => 'ON-WHEY-VAN-5LB',
            'barcode' => '748927028676',
            'size' => '5 lbs (2.27 kg)',
            'flavor' => 'French Vanilla',
            'default_cost_price' => 60000.00,
            'default_sale_price' => 85000.00,
            'member_sale_price' => 80000.00,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'کۆمپانیای تەندروستی بۆ هاوردەکردنی تەواوکەر',
            'phone' => '+9647504445566',
            'email' => 'supplier@supplements.test',
            'address' => 'هەولێر - ناوچەی پیشەسازی',
            'current_balance' => 0.00,
        ]);

        // دروستکردنی وەجبەی FEFO بۆ شوکۆلاتە:
        // باچی ١: بەسەرچوونی نزیکتر (2026-12-01) - 10 دانە
        InventoryBatch::create([
            'shop_id' => $shop1->id,
            'variant_id' => $varChoc5lb->id,
            'supplier_id' => $supplier->id,
            'batch_number' => 'LOT-2026-001',
            'expiry_date' => '2026-12-01',
            'unit_cost_price' => 60000.00,
            'quantity_received' => 10,
            'quantity_available' => 10,
            'is_blocked' => false,
        ]);

        // باچی ٢: بەسەرچوونی دوورتر (2027-06-01) - 20 دانە
        InventoryBatch::create([
            'shop_id' => $shop1->id,
            'variant_id' => $varChoc5lb->id,
            'supplier_id' => $supplier->id,
            'batch_number' => 'LOT-2026-002',
            'expiry_date' => '2027-06-01',
            'unit_cost_price' => 62000.00,
            'quantity_received' => 20,
            'quantity_available' => 20,
            'is_blocked' => false,
        ]);

        // باچێکی بەسەرچوو بۆ تاقیکردنەوەی بلۆککردن
        InventoryBatch::create([
            'shop_id' => $shop1->id,
            'variant_id' => $varChoc5lb->id,
            'supplier_id' => $supplier->id,
            'batch_number' => 'LOT-EXPIRED-TEST',
            'expiry_date' => '2026-01-01',
            'unit_cost_price' => 50000.00,
            'quantity_received' => 5,
            'quantity_available' => 5,
            'is_blocked' => false,
        ]);

        // دەستپێکردنی شفتی کراوەی سندوق بۆ کاشێری هۆڵی ١
        ShopCashShift::create([
            'shop_id' => $shop1->id,
            'cashier_user_id' => $gym1Cashier->id,
            'opened_at' => now(),
            'opening_cash' => 50000.00,
            'expected_cash' => 50000.00,
            'status' => 'OPEN',
        ]);

        // ٨. تۆمارکردنی نموونەی هاتن و ڕۆشتنی یاریزانان بۆ ئەمڕۆ (Daily Attendance)
        $member4 = $membershipService->registerMember([
            'full_name' => 'دیاری کامەران',
            'phone' => '+9647504443322',
            'gender' => 'male',
        ], $gym1->id);
        $membershipService->subscribeToPlan($member4, $planMonthlyGym1, $gym1Receptionist->id, 0, 50000.00);

        $member5 = $membershipService->registerMember([
            'full_name' => 'ڕەنجدەر بەرزان',
            'phone' => '+9647505556677',
            'gender' => 'male',
        ], $gym1->id);
        $membershipService->subscribeToPlan($member5, $planMonthlyGym1, $gym1Receptionist->id, 0, 50000.00);

        $member6 = $membershipService->registerMember([
            'full_name' => 'شاڵاو تۆفیق',
            'phone' => '+9647506667788',
            'gender' => 'male',
        ], $gym1->id);
        $membershipService->subscribeToPlan($member6, $planMonthlyGym1, $gym1Receptionist->id, 0, 50000.00);

        $gateCentral = AccessDevice::where('gym_id', $gym1->id)->first();

        // یاریزانی ١: ئێستا لە ژوورەوەیە (لە پێش ١ کاتژمێر و ١٥ خولەک)
        MemberAttendance::create([
            'gym_id' => $gym1->id,
            'member_id' => $member1->id,
            'membership_id' => $member1->activeMembership?->id,
            'access_device_id' => $gateCentral?->id,
            'check_in_at' => now()->subMinutes(75),
            'status' => 'INSIDE',
            'check_in_method' => 'qr_code',
        ]);

        // یاریزانی ٤: ئێستا لە ژوورەوەیە (لە پێش ٤٠ خولەک)
        MemberAttendance::create([
            'gym_id' => $gym1->id,
            'member_id' => $member4->id,
            'membership_id' => $member4->activeMembership?->id,
            'access_device_id' => $gateCentral?->id,
            'check_in_at' => now()->subMinutes(40),
            'status' => 'INSIDE',
            'check_in_method' => 'card_scan',
        ]);

        // یاریزانی ٥: هاتووە و دەرچووە (ماوەی ١ کاتژمێر و ٤٠ خولەک)
        MemberAttendance::create([
            'gym_id' => $gym1->id,
            'member_id' => $member5->id,
            'membership_id' => $member5->activeMembership?->id,
            'access_device_id' => $gateCentral?->id,
            'check_in_at' => now()->subMinutes(130),
            'check_out_at' => now()->subMinutes(30),
            'duration_minutes' => 100,
            'status' => 'CHECKED_OUT',
            'check_in_method' => 'card_scan',
        ]);

        // یاریزانی ٦: هاتووە و دەرچووە (ماوەی ٢ کاتژمێر)
        MemberAttendance::create([
            'gym_id' => $gym1->id,
            'member_id' => $member6->id,
            'membership_id' => $member6->activeMembership?->id,
            'access_device_id' => $gateCentral?->id,
            'check_in_at' => now()->subMinutes(180),
            'check_out_at' => now()->subMinutes(60),
            'duration_minutes' => 120,
            'status' => 'CHECKED_OUT',
            'check_in_method' => 'qr_code',
        ]);
    }
}
