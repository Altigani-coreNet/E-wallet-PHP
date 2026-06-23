<?php

return [
    'validation_failed' => 'فشل التحقق من البيانات',
    'details_validated' => 'تم التحقق من البيانات بنجاح',

    'email_unique' => 'هذا البريد الإلكتروني مسجّل مسبقاً.',
    'phone_unique' => 'رقم الهاتف هذا مسجّل مسبقاً.',
    'password_confirmed' => 'تأكيد كلمة المرور غير متطابق.',

    'user_registered' => 'تم تسجيل المستخدم بنجاح',
    'user_registration_failed' => 'فشل تسجيل المستخدم: :error',

    'user_not_authenticated' => 'المستخدم غير مصادق عليه',
    'merchant_not_found' => 'لم يتم العثور على المتجر',
    'no_merchant_record' => 'لا يوجد سجل تاجر لهذا المستخدم. يرجى إكمال التسجيل الأولي أولاً.',

    'merchant_registered' => 'تم تسجيل المتجر بنجاح',
    'merchant_registration_failed' => 'فشل تسجيل المتجر: :error',
    'merchant_profile_updated' => 'تم تحديث ملف المتجر بنجاح',
    'merchant_profile_update_failed' => 'فشل تحديث ملف المتجر: :error',

    'verification_email_sent' => 'تم إرسال رمز التحقق عبر البريد الإلكتروني بنجاح',
    'verification_phone_sent' => 'تم إرسال رمز التحقق عبر الهاتف بنجاح',
    'verification_send_failed' => 'فشل إرسال رمز التحقق: :error',
    'email_verified' => 'تم التحقق من البريد الإلكتروني بنجاح',
    'phone_verified' => 'تم التحقق من الهاتف بنجاح',
    'invalid_verification_code' => 'رمز التحقق غير صالح',

    'merchant_continuation_sent' => 'تم إرسال بريد متابعة تسجيل المتجر بنجاح',
    'merchant_continuation_failed' => 'فشل إرسال بريد متابعة تسجيل المتجر: :error',

    'contract_terms_invalid_lang' => 'لغة غير صالحة. اللغات المدعومة: en، ar',
    'contract_terms_not_found' => 'لم يتم العثور على شروط العقد للغة المحددة',
    'contract_terms_retrieved' => 'تم جلب شروط العقد بنجاح',
    'contract_terms_error' => 'حدث خطأ أثناء جلب شروط العقد',

    // OTP email
    'verification_code_subject' => 'رمز التحقق الخاص بك',
    'verification_code_title' => 'رمز التحقق الخاص بك',
    'verification_code_body' => 'يرجى استخدام هذا الرمز للتحقق من حسابك. ينتهي صلاحية الرمز خلال 10 دقائق.',
    'verification_code_ignore' => 'إذا لم تطلب هذا الرمز، يرجى تجاهل هذا البريد.',

    // Account created email
    'account_created_subject' => 'تم إنشاء الحساب بنجاح — أكمل تسجيل المتجر',
    'account_created_heading' => 'تم إنشاء الحساب بنجاح!',
    'account_created_subtitle' => 'تم إنشاء حساب المتجر الخاص بك. يمكنك الآن متابعة عملية التسجيل.',
    'account_created_greeting' => 'مرحباً :name،',
    'account_details' => 'تفاصيل الحساب',
    'label_name' => 'الاسم',
    'label_email' => 'البريد الإلكتروني',
    'label_username' => 'اسم المستخدم',
    'label_phone' => 'الهاتف',
    'next_steps' => 'الخطوات التالية',
    'next_steps_hint' => 'لتفعيل حسابك وبدء قبول المدفوعات، أكمل تسجيل المتجر.',
    'complete_registration' => 'إكمال تسجيل المتجر',
    'you_will_need' => 'ستحتاج إلى:',
    'step_company_profile' => 'إضافة ملف الشركة',
    'step_documents' => 'رفع مستندات العمل',
    'step_verification' => 'إكمال التحقق (اعرف عميلك / اعرف نشاطك)',
    'step_go_live' => 'البدء وقبول المدفوعات',
    'btn_complete_registration' => 'إكمال تسجيل المتجر',
    'btn_login' => 'تسجيل الدخول إلى حسابك',
    'account_created_thanks' => 'شكراً لاختيارك Core Net Pay لدعم أعمالك.',

    // Merchant continuation email
    'merchant_continuation_subject' => 'تسجيل المتجر — الخطوات التالية',
    'merchant_continuation_heading' => 'مرحباً بك في منصتنا!',
    'merchant_continuation_subheading' => 'تسجيل المتجر الخاص بك يسير بشكل جيد',
    'merchant_continuation_greeting' => 'مرحباً :name،',
    'merchant_continuation_intro' => 'شكراً لإكمال خطوة مستندات العمل. يسعدنا انضمام :business إلى منصتنا!',
    'merchant_continuation_whats_next' => 'ماذا بعد؟',
    'merchant_continuation_review' => 'مراجعة: سيقوم فريقنا بمراجعة مستنداتك ومعلومات العمل',
    'merchant_continuation_timeline' => 'المدة المتوقعة: ستتلقى رداً خلال 24–48 ساعة',
    'merchant_continuation_activation' => 'تفعيل الحساب: بعد الموافقة سيتم تفعيل حساب المتجر بالكامل',
    'merchant_continuation_login' => 'الدخول: ستتلقى بيانات الدخول إلى لوحة المتجر',
    'merchant_continuation_can_do' => 'ما يمكنك فعله الآن',
    'merchant_continuation_explore' => 'استكشاف ميزات المنصة',
    'merchant_continuation_guidelines' => 'مراجعة إرشادات وسياسات المتجر',
    'merchant_continuation_docs' => 'تجهيز أي مستندات إضافية إن لزم',
    'merchant_continuation_support' => 'التواصل مع الدعم عند الحاجة',
    'merchant_continuation_access' => 'الوصول إلى حسابك',
    'merchant_continuation_need_help' => 'تحتاج مساعدة؟',
    'merchant_continuation_footer' => 'شكراً لاختيارك منصتنا.',
    'merchant_continuation_regards' => 'مع أطيب التحيات،',
    'merchant_continuation_team' => 'فريق التسجيل',
    'merchant_continuation_sent_to' => 'تم إرسال هذا البريد إلى :email. إذا لم تطلبه، يرجى تجاهله.',
];
