<?php

namespace Database\Seeders;

use App\Models\EnBnValueList;
use Illuminate\Database\Seeder;

    class EnBnValueListSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EnBnValueList::create(['id'=>1, 'type'=>'support_center_facilities', 'bn'=> 'আপনার একটি ১০/১০ ফিট অফিস এর ব্যবস্থা করতে হবে।']);
        EnBnValueList::create(['id'=>2, 'type'=>'support_center_facilities', 'bn'=> 'ভালো বিদ্যুৎ এর সংযোগ এবং ভালো মানের ক্যাবল সংযোগ এর ব্যবস্থা করতে হবে।']);
        EnBnValueList::create(['id'=>3, 'type'=>'support_center_facilities', 'bn'=> '৪ ঘণ্টা অথবা তার বেশী ক্ষমতা সম্পন্ন আইপিএস এর ব্যবস্থা করতে হবে ।']);
        EnBnValueList::create(['id'=>4, 'type'=>'support_center_facilities', 'bn'=> 'প্রয়োজনে জেনারেটর এর সংযোগের ব্যবস্থা থাকতে হবে ।']);
        EnBnValueList::create(['id'=>5, 'type'=>'support_center_facilities', 'bn'=> 'প্রয়োজনে ২৪ ঘণ্টা এসি চালানোর বাবস্থা থাকতে হবে ।']);
        EnBnValueList::create(['id'=>6, 'type'=>'support_center_facilities', 'bn'=> 'আপানার অফিসে ২৪ ঘণ্টা যাতায়াত করার ব্যবস্থা থাকতে হবে ।']);
        EnBnValueList::create(['id'=>7, 'type'=>'support_center_facilities', 'bn'=> '২৪ ঘণ্টা ফোনে সাপোর্ট দেওয়ার ব্যবস্থা করতে হবে ।']);
        EnBnValueList::create(['id'=>8, 'type'=>'support_center_facilities', 'bn'=> 'ক্যাবল সাপোর্ট দেওয়ার জন্য লোকবল প্রয়োজন মত থাকতে হবে ।']);
        EnBnValueList::create(['id'=>9, 'type'=>'support_center_facilities', 'bn'=> 'লোকাল মার্কেটিং করে সাপোর্ট পার্টনার সংযুক্ত করা।']);
        EnBnValueList::create(['id'=>10, 'type'=>'support_center_facilities', 'bn'=> 'আপনার ক্যাবল এবং সাপোর্ট পার্টনার এর সংযোগ এর রক্ষনা-বেক্ষন করতে হবে।']);
        EnBnValueList::create(['id'=>11, 'type'=>'support_categories', 'bn'=> 'নতুন সংযোগ', 'en'=>'New Connection']);
        EnBnValueList::create(['id'=>12, 'type'=>'support_categories', 'bn'=> 'সাইন-ইন/রেজিস্ট্রেশন', 'en'=>'Sign-In/Registration']);
        EnBnValueList::create(['id'=>13, 'type'=>'support_categories', 'bn'=> 'পাসওয়ার্ড/ওটিপি সমস্যা', 'en'=>'Password/OTP Problem']);
        EnBnValueList::create(['id'=>14, 'type'=>'support_categories', 'bn'=> 'বিল প্রদান', 'en'=>'Bill Payment']);
        EnBnValueList::create(['id'=>15, 'type'=>'support_categories', 'bn'=> 'প্যাকেজ ক্রয়/পরিবর্তন', 'en'=>'Package Purchase/Change']);
        EnBnValueList::create(['id'=>16, 'type'=>'support_categories', 'bn'=> 'ধীর-গতি/ইন্টারনেট নেই', 'en'=>'Slow/No Internet']);
        EnBnValueList::create(['id'=>17, 'type'=>'support_categories', 'bn'=> 'গেমিং সমস্যা', 'en'=>'Gaming Issue']);
        EnBnValueList::create(['id'=>18, 'type'=>'support_categories', 'bn'=> 'কভারেজ', 'en'=>'Coverage']);
        EnBnValueList::create(['id'=>19, 'type'=>'support_categories', 'bn'=> 'আইডি/পাসওয়ার্ড পরিবর্তন', 'en'=>'ID/Password Change']);
        EnBnValueList::create(['id'=>20, 'type'=>'support_categories', 'bn'=> 'অন্যান্য', 'en'=>'Others']);
    }
}
