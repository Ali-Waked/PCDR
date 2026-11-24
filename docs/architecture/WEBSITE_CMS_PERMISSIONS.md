# صلاحيات وأدوار موقع الويب CMS

**التاريخ:** 2026-11-23
**الجمهور:** المطوّرون ومديرو الوصول التقني
**الحالة:** وثيقة تقنية فقط؛ لا تمنح صلاحية اعتماد مؤسسي.

---

## 1. المبدأ الحاكم

المرحلة الثانية هي المالك الوحيد لنموذج RBAC. نظام CMS لا ينشئ صلاحيات جديدة موازية ولا يمنح سلطاته الخاصة؛ يقرأ فقط أسماء الصلاحيات المعتمدة في `config/permissions.php` ويفوّض التحقق إلى `RbacService` عبر `CmsAccess` و`EnsureCmsPermission`.

قاعدة حاسمة: **أي صلاحية يصنّفها `RbacService::isAuthorityPermission()` تُرفض دائمًا في CMS**، بما في ذلك هوية التمهيد المحلية للتطوير. لا يوجد مسار تقني يمنح اعتمادًا مؤسسيًا.

---

## 2. تكرار الصلاحيات

تم توحيد الاسمين المكررين لحالة الاستفسار:

- الاسم المعتمد: `inquiries.update`
- الاسم المُزال: `inquiries.update_status`

أُزيل الاسم المكرر من `config/permissions.php` و`config/cms.php` و`CmsRolesSeeder.php`. لا توجد صلاحية أخرى بنفس الدلالة في الكتالوج الحالي.

---

## 3. الأدوار الوظيفية المُعرّفة

تُعرّف `CmsRolesSeeder` الأدوار التالية كتعريفات فقط:

| الدور | الوظيفة التقنية | الاعتماد المؤسسي |
|---|---|---|
| `website_administrator` | إدارة كاملة لمحتوى الويب والوسائط والإعدادات العامة والاستفسارات وقوالب البريد | لا |
| `content_editor` | إنشاء وتحرير المحتوى العام والوسائط | لا |
| `publisher_reviewer` | عرض المحتوى فقط للمراجعة؛ **محجوز**؛ لا نشر ولا رد | لا |
| `contact_manager` | إدارة الاستفسارات والردود والملاحظات | لا |
| `contact_support` | عرض الاستفسارات المُسندة فقط | لا |

**لا يُنشئ مُهيّئ الأدوار أي مستخدم، ولا يُعيّن أي دور إلى أي حساب حقيقي، ولا يعيّن أي منصب مؤسسي.** `role_user` يبقى فارغًا بعد البذر في بيئة الاختبار.

---

## 4. صلاحيات CMS المستخدمة

- عرض المحتوى: `news.view`, `programs.view`, `projects.view`, `pillars.view`, `publications.view`, `media.view`
- التحرير: `news.create/update/publish/unpublish` ونظائرها لوحدات المحتوى
- الوسائط: `media.upload`, `media.update`, `media.delete`
- الاستفسارات: `inquiries.view`, `inquiries.view_assigned`, `inquiries.update`, `inquiries.assign`, `inquiries.add_internal_note`, `inquiries.reply`
- القوالب: `email_templates.view/create/update/delete`
- الإعدادات: `settings.update` للإعدادات العامة فقط
- IAM: `users.*`, `roles.*`, `permissions.view`, `audit_logs.view`

الأسماء `users.assign_roles`, `users.update`, `audit_logs.view` هي الأسماء المرجعية المعتمدة؛ لم تُنشأ أسماء بديلة مكررة.

---

## 5. الحماية على الخادم

1. `bootstrap/app.php` يطبّق `EnsureCmsPermission` و`EnsureDashboardFeatureAvailable` على مسارات CMS/Locale.
2. كل متحكم CMS يستدعي `authorize()` قبل أي عملية، ويفشل مغلقًا (403).
3. فهرس الاستفسارات يقيد السجلات غير المُسندة لمستخدمي `inquiries.view_assigned`.
4. المسارات المخفية تُحجب على مستوى `dashboard_features` حتى لو عُرفت صلاحية تشغيلية قديمة؛ استثناء Platform Owner مقصود لأغراض الانحدار التقني فقط.

---

## 6. الحكم

النموذج الحالي يوفّر فصلًا واضحًا: صلاحيات تقنية لإدارة محتوى الويب، وأدوار وظيفية بلا تعيينات، وحظر صريح لصلاحيات الاعتماد المؤسسي. لا يُعدّ أي دور أو صلاحية تفويضًا رسميًا من جهة حكومية أو مؤسسية.
