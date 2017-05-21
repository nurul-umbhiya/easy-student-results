=== Easy Student Results ===
Contributors: zikubd
Donate link: https://www.nurul.me/product/easy-student-results-needs-support/
Tags: academic, academic result, student, student result, student result management system, result management system, education, result system, create marksheet online, online marksheet, online marksheet creator, result, easy student result, school, college, university, school result, college result, university result, create marksheet online, emarksheet
Requires at least: 4.0.0
Tested up to: 4.7.5
Stable tag: 1.8
Version: 1.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Result Management System for School, College and University. Use [esr_results] to display result and [esr_students] to display student list.

== Description ==

Fully Featured Result Management System for School, College and University. You can use Student Result plugin for Employee Listing too.
A free Android Apps is also available for this plugins. With this Android app (Version 4.4.4 and later), you can search students, view results etc.
Dedicated settings page is available for Apps api. From there you can change your app settings dynamically. Download this
app from this [link](https://www.nurul.me/product/easy-student-results-android-apps/ "Easy Student Results")

Added a new shortcode [esr_results2], by this shortcode, student can search their own result, result can be searched by
Roll no (by default) or by Registration no [esr_results2 search_by='reg']. Note that, while using [esr_results2] shortcode, all students roll number / registration number should be unique. Otherwise you'll get no result error or wrong result. If you can't make sure unique Roll No / Registration No for each students please use [esr_results]. [esr_results] will give you accurate result no matter what.

= Features =

*   Unlimited Department
*   Unlimited Batch
*   Unlimited Semesters
*   Unlimited Students
*   Unlimited Courses
*   Unlimited Results
*   Shortcode For Viewing Result and Student List
*   Student Can be Searched By Department, Batch and Semesters
*   Result Can be searched by Exam, Departments, Batches and Semesters
*   Result Print Facility
*   Detailed Plugin Settings Page
*   Custom Post Type Used For Students and Courses with lots of filter options
*   Default WordPress look and feel for every page
*	Bootstrap Templates for Students and Result shortcode
*   Plugin is translation ready
*   Used WordPress Transients API for complex mysql queries
*   WordPress Multisite support


= Premium AddOns =
Premium AddOns are available for this plugin, like private result search, new shortcodes for searching result by exam, year and roll no etc. For more details please visit [here](https://www.nurul.me/shop/ "Easy Student Results Premium AddOns") for more details.

*   [Marks Entry Frontend](https://www.nurul.me/product/marks-entry-frontend/ "Easy Student Results : Marks Entry Frontend")
*   [Private Result Search](https://www.nurul.me/product/easy-student-results-private-result-search/ "Easy Student Results : Private Result Search")
*   [Advanced Search Plugin](https://www.nurul.me/product/easy-student-results-advanced-search-plugin/ "Easy Student Results : Advanced Search Plugin")
*   [List Entry Marks](https://www.nurul.me/product/easy-student-results-list-entry-marks/ "Easy Student Results : List Entry Marks")

= Feature Request =

For new feature please sent me an email at contact(at)nurul.me with subject: Feature Request For Easy Student Results

= Easy Student Results Needs Your Support =

It is hard to continue development and support for this free plugin without contributions from users like you. If you enjoy using Easy Student Results and find it useful, please consider [__making a donation__](https://www.nurul.me/product/easy-student-results-needs-support/). Your donation will help encourage and support the plugin's continued development and better user support.

== Installation ==

1. Upload `easy_student_results` directory to the `/wp-content/plugins/`  directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Upgrade Notice ==


== Screenshots ==

1. Frontend Result Listing
2. Frontend Result Details Page
3. Frontend Students Listing
4. Add / Edit Department and Semester Page
5. Add / Edit Batch
6. Add New Students
7. Students List
8. Add New Course
9. Course List
10. Exam Record List
11. Enter Marks: Student List
12. Enter Marks


== Changelog ==

= 1.8 =
* Updated Codebase
* Fixed Some issues for shortcodes
* Added some hooks

= 1.7 =
* Fixed [esr_results2] shortcode
* Now you can edit department

= 1.6 =
* Added a Delete button to Results Menu.
* Fixed a bug for Editing result where, while adding new student to existing result, already added students data get deleted
* Updated both [esr_results] and [esr_results2] shortcode, now you can show Results fields data on Student Information from Settings -> Shortcode Result tab
* Added a new settings field named "Who Can View Student Results Menu" under Student Results --> Settings --> Basic Settings, by this settings you can select user role of the plugin
* Added a new settings field named "Display Header/footer with Result ?" under Student Results --> Settings --> Shortcode Result, by enabling this setting, you can show header, footer content like company logo, any text, html content etc while displaying the result
* Added a new settings field named "Hide Search Field" under Student Results --> Settings --> Shortcode Result, by enabling this settings you can hide result search form while displaying result.


= 1.5 =
* Fixed translation issue for wrong text domain selected, now you can translate this plugin to your own language
* Fixed some css issues
* Fixed some php notices
* Code improvements

= 1.4 =

* Added uninstall.php file, now you can delete all plugins data while deleting this plugin. A new settings options is added under General Settings section.
* Solved a issue with multisite installation.
* Added Total Obtained Marks for [esr_results] and [esr_results2] shortcode
* Fixed a PHP notice for [esr_results2] shortcode


= 1.3 =
* Added multisite support. Now this plugin fully support WordPress Multisite
* Added new shortcode [esr_result2], now students can search their own result by entering roll number by default, or
by registration number [esr_results2 search_by='reg']
* Fixed a minor bug with mobile apps api

= 1.2 =
* Fixed a PHP notice on result shortcode
* Fixed Autoloading class issue on PHP Version 5.2
* Fixed Add New Exam error message on same exam name

= 1.1 =
* Fixed HTTPS url issue


= 1.0 =
* Initial Plugin Release

== Frequently Asked Questions ==

= What is the shortcode to display result in frontend ? =

Use [esr_results] in a page or post to display Result in frontend.

= What is the shortcode to display student list in frontend ? =

Use [esr_students] in a page or post to display Students in frontend.

= Can I use this plugin for Employee listing ? =

Yes. Using [esr_students] shortcode you can use this plugin as employee listing plugin. Each shortcode has its own
settings page in admin panel. From there, you can change every option you need.
