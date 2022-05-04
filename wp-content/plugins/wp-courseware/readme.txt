=== WP Courseware ===
Contributors: flyplugins
Plugin URI: https://flyplugins.com/wp-courseware
Author URI: https://flyplugins.com
Tags: WordPress LMS,WordPress eCourse,WordPress Courseware,WordPress Learning Management System
Requires PHP: 5.6.20
Requires at least: 4.8.0
Tested up to: 5.9.2
Stable tag: 4.8.19
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WordPress's leading Learning Management System (L.M.S.) plugin and is so simple you can create an online training course in minutes.

== Description ==

WP Courseware is a WordPress premium plugin that allows you to create an online curriculum or "e-course" also known as a learning management system. You simply engineer your course with modules and add units within the modules depending on how you want to set up your curriculum or sequential content. You can add video, audio, textual content, quizzes or even downloadable lessons to your units. You can order the units and quizzes by simply dragging and dropping within the various modules. Finally, a learning management system WordPress plugin that will make it easy to run your online training course. With WP Courseware, the sky is the limit! WP Courseware integrates with some of the most popular membership plugins like iThemes Exchange Membership, Paid Memberships Pro, MemberPress, WishList Member, Magic Member, S2 Member and more to come. A membership integration creates a powerful, fully automated, online membership e-learning selling platform.

== Installation ==

1. Go in to your WordPress Admin Panel.
2. Navigate to Plugins &rarr; Add New and click upload.
3. Browse for the wp-courseware.zip file and click upload.
4. Click Activate.

== Frequently Asked Questions ==

= Is WP Courseware compatible with membership plugins? =

WP Courseware is compatible with most membership plugins. However we do have integration with:

* WooCommerce
* Easy Digital Downloads
* Restrict Content Pro
* WishList Member
* Magic Member
* MemberPress
* Paid Memberships Pro
* Member Sonic
* Premise
* S2Member
* Memberium
* Digital Access Pass
* Ontraport

= Does WP Courseware support mobile interface? =

That all depends on your WordPress theme. If you theme supports mobile, then your online course should follow suite.

= What type of training material can I add to each unit? =

You can add anything you would normally add to a page or a post with in WordPress. You can add photos, video, audio, downloadable PDF's, hyperlinks etc...

= How do I update WP Courseware? =

When an updates is available you can update WP Courseware just like you would a plugin that is installed from the WordPress Plugin library. Keep in mind this requires you to have an active license subscription.

= Where can I find the tutorial videos? =

You can find our training videos in:

* The documentation section of the WP Courseware plugin
* Check out our [You Tube channel](http://www.youtube.com/flyplugins)

== Changelog ==

= 4.8.19 =
* Fix: Fixed several PHP 8 warnings

= 4.8.18 =
* New: Added Enfold Avia Layout support for course units
* Fix: Fixed issue where using post module in Divi would cause duplicate content


= 4.8.17 =
* New: Thrive Architect support

= 4.8.16 =
* Fix: Fixed issue where certificate blocks were available on other post types
* Fix: Fixed issue where content was not being displayed with Beaver Themer

= 4.8.15 =
* Fix: Various issues with visibility of unit content
* New: BeaverBuilder Themer support
* New: Divi Theme Builder support

= 4.8.14 =
* Fix: Fixed issue where password protected units were diplaying content
* Fix: Fixed issue where dripped content was displaying content
* Fix: Fixed issue where viewing unassigned units was causing a fatal error
* Tweak: Adjusted a couple of filters that were depricated in WordPress core

= 4.8.13 =
* Fix: Fixed issue where unit title is duplicated in the content

= 4.8.12 =
* Fix: Fixed issue where quiz "begin" and quiz "retake" buttons wouldn't initiate a quiz.

= 4.8.11 =
* Fix: Fixed issue where expired courses were listed on the course archive page
* Fix: Fixed issue where expired courses were listed on the account->courses page
* Fix: Fixed issue where expired courses were listed when using the course progress shortcode
* Fix: Fixed issue where expired course units were accessible
* Fix: Fixed issue with course progress widget throwing a PHP notice
* Fix: Fixed issue with course progress bar widget throwing a PHP notice

= 4.8.9 =
* Fix: Fixed issue where importing students failed when students name contained Cyrillic characters
* Fix: Fixed issue where unit tempalte was placing completion box above content

= 4.8.8 =
* Fix: Fixed issue where courses with status of draft, pending review, or private were still visible on course progress and course outline.
* New: Added new fonts for certificate.

= 4.8.7 =
* Fix: Fixed issue with download quiz results from student progress page.
* New: Added new fonts for certificate.

= 4.8.6 =
* Fix: Fixed the course expired text string to be translatable.
* Fix: Fixed issue where certificate uppercase text for characters with accents would remain lower case.
* Fix: Fixed issue where duplicating at the course or module level would not duplicate post_meta for units.
* Fix: Purchase and installment text was not translating properly.
* Fix: Fixed issue where translating the string "WP Courseware" would break Javascript on the settings page.

= 4.8.5 =
* Fix: Fixed frontend CSS issue.

= 4.8.4 =
* Fix: Fixed issue where units would display content even if course was expired.
* Fix: Fixed several PHP 8 warnings.
* Fix: Fixed issue where Stripe was not detecting site SSL and therefore would default to test mode.
* Fix: Fixed issue where upload question type was not handling error checking correctly.
* Fix: Fixed issue where instructor role was unable to create/edit certificates.
* Fix: Fixed alignment issues with certificates.
* Fix: Fixed several strings that were not translatable.
* Tweak: Updated TCPDF library for PHP 8 compatibility.
* Tweak: Added filter to switch off drip for course units.

= 4.8.3 =
* Fix: Fixed issue where syntax was causing fatal error on certain versions of PHP 7.2.x.

= 4.8.2 =
* Fix: Fixed WP Courseware Reports on dashboard to display correct symbol when amount is 0.
* Fix: Fixed issue where accordion menu in course settings was not opening/closing properly.
* Fix: Fixed issues causing PHP notices with PHP 8.
* Fix: Fixed issue where activating incorrect license would not display an admin message.
* Fix: Fixed issue where order item tooltip would always show discount amount in 'dollars'.
* Fix: Fixed issue where emailing classroom or individual in classroom would recieve email from admin rather than instructor.
* Fix: Fixed issue where exporting survey results was html encoding characters.
* Fix: Fixed issue on detailed student quiz page where time stamp was displayed as universal time rather than local time.
* Tweak: Modified the spinner in checkout to be centered on screen when making purchase.
* Tweak: Added quiz question images (not responses) to display on the detailed student quiz page.
* Tweak: Added a download quiz results button to the detailed student quiz page for easy access.
* Tweak: Updated the plugin updater function.
* New: Added new filter 'wpcw_course_single_outline_args' to the course single template to modify the course outline paramters.

= 4.8.1 =
* Fix: Fixed issue where dejavusans PDF font was missing.
* Tweak: Courses set to manual enrollment will not display enroll button on front end.

= 4.8.0 =
* New: Fully customizable certificates.
* New: All major elements are duplicatable (courses, modules, units, quizzes).
* New: Instructor feedback per question.
* New: Course expiration with option to reset students progress.

= 4.7.3 =
* Fix: Fixed issue where end users were unable to add quiz questions.

= 4.7.2 =
* Fix: Fixed issue where deleting a course or a module would leave units orphaned and unusable.
* Fix: Fixed issue where instructor would revert back when updating a course.
* Fix: Fixed 404 issue with order recieved page.
* Fix: Fixed issue where slashes were removed when adding or updating unit in the course builder.
* Fix: Fixed issue where quiz timer results were not displaying for non-blocking quiz.
* Update: Updated the Quiz Timer JavaScript library.

= 4.7.1 =
* Fix: Dashboard report widget was reporting incorrect total sales.

= 4.7.0 =
* New: Membership feature added to restrict access to WordPress pages and posts based on enrollment.
* Fix: Replace missing "No Courses" notice.
* Fix: Dashboard report widget was reporting incorrect sales and student enrollment.

= 4.6.13 =
* Fix: Improved compatibility with WordPress 5.5

= 4.6.12 =
* Fix: Stabilized Course Builder saving.
* Fix: PayPal receipts correctly showing paid tax.

= 4.6.11 =
* Fix: Disappearing prerequisites, bundles and instructors on Course Builder save.

= 4.6.10 =
* Fix: Remove erroneously saved backslashes from email messages.

= 4.6.9 =
* Fix: Newly added units were saving with default content.
* Fix: Unit administration was stripping iframe content.

= 4.6.8 =
* Fix: PayPal installment payments no longer display unapplied discounts.
* Fix: Improved handling of Stripe installment payments.
* Fix: Rare Javascript errors that prevented some screen interactions from completing.
* Tweak: Improved display of order and payment information.
* Tweak: Better handling of special characters in course content.

= 4.6.7 =
* Fix: Multiple add to cart buttons on the same page refreshed incorrectly.
* Fix: Course Unit drip date did not save correctly when adding a Unit in the course builder.
* Fix: Course Unit drip interval number saved to an incorrect format when added in the course builder.
* Fix: Quiz's with long titles would not save properly in the course builder.
* Fix: Course filters to change default values worked incorrectly.
* Fix: Stripe Api exception handling with latest Stripe SDK.
* Fix: Randomized quiz option failed to validate properly.
* Tweak: Updated the Stripe SDK to latest version.
* Tweak: Updated the TCPDF library to latest version.
* Tweak: PHP Minimum Requirement updated to be 5.6.0.

= 4.6.6 =
* Fix: Unit access issue with enrolled students.

= 4.6.5 =
* Fix: Gutenberg block course enrollment button ajax error.
* Fix: Stripe would process installment/subscription payment on incorrect webhook.
* Fix: Unit access was not allowed on an unassigned unit.
* Fix: Account password reset conflicts with WooCommerce.
* Fix: Styling issues in WordPress 5.3.

= 4.6.4 =
* New: Students have the ability to reset their own progress.
* New: Classroom students can now be sorted by progress.
* New: Student Account Courses settings section to quickly enable/disable course features.
* New: Multiple choice question can now be forced to display as checkboxes.
* Tweak: Updated Font Awesome library to version 5.11.2.
* Tweak: Instructors only see courses they have authored when editing a student profile.
* Fix: Fixed the certificate upload mechanism to avoid automatic resizing in WordPress 5.3.
* Fix: Quiz multiple choice question with more than 5 correct answers validated incorrectly.
* Fix: Course builder Unit description would not allow embeds.
* Fix: Password reset key would not be correct in certain instances.
* Fix: Exception class would throw fatal error on cart page.

= 4.6.3 =
* New: Stripe Payment Gateway SCA/3DS2 compliance.
* New: Added a `redirect` attribute to `[wpcourse_enroll]` shortcode to immediately redirect the enrolled user to the first available unit in course.
* Tweak: Move "Update Access" button to top next to "View Detailed Progress" button on the Student Details admin page.
* Fix: `WPCW_courses_getCourseList` included 'auto-drafts' in the result set.
* Fix: Email classroom students would only send to first 20 students.
* Fix: Email student action on detailed quiz / survey results would send to Admin not student.
* Fix: Classroom bulk action to remove student from course would remove the student from all enrolled courses.
* Fix: Bulk action would not redirect properly when no bulk action method matched.
* Fix: Conflict with Give plugin and TCPDF font path for certificate and pdf generation.
* Fix: DB Table names and global $wpcwdb references were incorrect when using switch_to_blog().

= 4.6.2 =
* New: Added a `hide_completed` attribute to the Course Progress shortcode. Credit [@jarekkacprzak](https://profiles.wordpress.org/jarekkacprzak)
* New: Ability to advance to next Unit automatically when `Marked as Complete` is clicked.
* Fix: Certificate background image does not display correctly a sub-directory install.
* Fix: Updated Emogrifier library to be compatible with PHP 7.3.2.
* Fix: Instructor role could not edit course categories and tags.
* Fix: Changed block editor post type support hook priority to be compatible with Visual Composer.

= 4.6.1 =
* Fix: Conflict with Divi builder on Unit single pages.
* Fix: Conflict with Accelerated Mobile Pages installation wizard.
* Fix: Conflict with WooCommerce Account Pages.
* Fix: Conflict with other plugins that use Font Awesome 5.
* Fix: Relative path and url generation for quiz question images.
* Fix: Validation rule when saving course and general settings with multi-select.

= 4.6.0 =
* New: Courses can now be configured with bundles.
* New: Installments payment option for courses.
* New: New shortcode for creating a link to the next available unit in a course. Shortcode is [wpcourse_next_available_unit] and the attributes include "course" (required - course ID), "text" (optional - Anchor Text) and "class" (optional - CSS Class).
* New: Added  course description (course_desc) and pre-requisite (course_prerequisites) attributes to the [wpcourse_progress] shortcode so they will display respectively with in course progress shortcode output.
* New: Added course description and pre-requisite options to the course progress Gutenberg block.
* New: Email button added on the student quiz grading screen. Button opens email modal to email student directly while on page.
* New: Added the "Completed Unit" email for when student completes a unit. The email template is customizable and can be sent to the student as well as the admin/instructor.
* New: Added different CSS classes for the next and previous buttons on the course unit.
* New: Added filter to the quiz grade book page to easily filter for students that have a quiz the requires manual grading.
* New: Added new course progress bar widget.
* New: Added new course progress bar shortcode [wpcourse_progress_bar]. Attributes include "course" (required - course ID), "show_title" (optional - show course title), "show_desc" (optional - show course description).
* New: Added progress bar Gutenberg block. Course title and course description can be enabled/disabled.
* New: Unit drip schedule can be set directly from unit editor within the course builder on the unit editor modal.
* New: Unit teaser/preview. Now the unit can be set as a teaser or preview which will allow any visitor to your site to preview the unit with out being registered or logged in.
* New: Unit teaser/preview option added to the unit editor with in the course builder on the unit editor modal.
* New: Allow admins/instructors the ability to preview course units without being prohibited by a unit that has been configured with a drip schedule.
* New: Allow admins/instructors the ability to navigate through courses freely without being prohibited by a blocking quiz.
* Tweak: Updated the TCPDF library.
* Fix: Issue with WP Courseware Emails not using the correct Emogrifier class when WooCommerce is installed.
* Fix: Email modal on student profile page did not display a TinyMCE for the email content.
* Fix: Classroom and Student data tables now display the first and last name if exists and display name otherwise.
* Fix: Cancelled and Failed Order emails were failing to trigger at status change.
* Fix: Subscription suspended emails were failing to trigger at status change.
* Fix: PayPal PDT confirmation would incorrectly generate two New Order emails if url scheme was different than the host site.

= 4.5.2 =
* New: Interface for administrators to change course instructors.
* Tweak: Added 'course_author' attribute to the courses shortcode to display a list of courses by author.
* Dev: Added function 'wpcw_get_student_progress_next_course_unit' to allow developers to get next available unit.
* Fix: Quiz questions that are assigned to a quiz with a different author now display a notice that editing is disabled.
* Fix: Conflict with Memberium WordPress plugin.
* Fix: Enable Taxes checkbox did not display the tax percentage input when checked.
* Fix: Tax amount is now calculated after applied discounts.
* Fix: Tax amount is set to zero when an applied discount causes the subtotal to be zero.
* Fix: PayPal Gateway -- when the first/initial payment total is zero, a special trial is setup to allow a subscription to be processed.
* Fix: PayPal Gateway -- upon special trial setup, an initial payment order is setup to allow tracking of all payments and to allow completion of the initial order.

= 4.5.1 =
* New: WordPress 5.0 / Gutenberg support for course, course progress, course list and enroll button short-codes.
* New: WordPress 5.0 / Gutenberg support for course units.
* Fix: Under certain conditions unit ordering within the course builder would time out and not save.
* Fix: Saving a blank endpoint did not remove the endpoint from the student account navigation.
* Fix: Unassigned units displayed publicly under certain conditions.
* Fix: Various text domain strings were misspelled.

= 4.5.0 =
* New: Coupons.
* New: Full integration with S3 Media Maestro.
* New: Ability to reset entire classroom to a certain point on the course.
* New: Bulk de-enroll functionality on the classroom page.
* New: Option to enroll an entire classroom into another course.
* New: Added "order" and "orderby" parameters to the "wpcw_courses" shortcode.
* Fix: Enrollment shortcode did not display "you are already enrolled" when the shortcode was used by itself.
* New: Support for emoji characters in open ended quiz questions.
* New: Admins can now search for students by username, first name, and last name.
* Fix: Conflict with certain plugins that contain the select2 library.
* Fix: Add media button on the student email modal.
* Fix: Compatibility with updated MathJax and Latex plugins.
* Dev: Added filter "wpcw_checkout_disable_scripts" to completely disable the checkout scripts.

= 4.4.6 =
* Tweak: Courses, modules, units, and quizzes are now linked on the detailed student progress report.
* Fix: Course emails were not being sent as type html.
* Fix: Translation RTL compatibility for Next/Previous Unit buttons.
* Fix: Compatibility with S3 Media Maestro in the course builder modals.
* Fix: Ability to remove a Unit template after it's been set.

= 4.4.5 =
* Fix: Quiz notification icons did not display when manual grading was required.
* Fix: Course prerequisite was evaluated incorrectly.
* Fix: Course archive included extra "Enroll Now" text before the enroll button.
* Fix: Admin bar link to view courses now displays the correct label.
* Fix: Enroll/Payment buttons were not disabled when a membership integration was active.
* Fix: Errors in various course_unit post type queries.

= 4.4.4 =
* New: Frontend Unit label can now be changed to Lesson, Lecture, or a custom label.
* New: Compatability with DigiMember 3.0.
* Fix: Fixed various translation strings.
* Fix: License updating issue with Cloudflare.
* Fix: Multisite network admin error when WP Courseware is active.
* Fix: Course Builder - Unit description could not be deleted.
* Fix: Course preview would not produce the correct module and unit permalink.

= 4.4.3 =
* Fix: Issue where license key would not always save and activate on certain sites thus causing license activation issues.
* Fix: Course unit content would sometimes appear public depending on query.
* Fix: An international country that does not require a state would validate as if a state were required.
* Fix: The course enroll button would not appear when the course archive template used excerpts.
* Fix: Course quick edit did not correctly update the course title.
* Fix: Builder: Quiz title and description were not marked as required.
* Fix: Fatal error when editing with WP Bakery frontend visual builder.
* Tweak: Improved the data updater process.

= 4.4.2 =
* Fix: Course builder would not allow more than 20 units or 20 modules to be visible.
* Fix: The unit order was incorrect when inserting units in bulk with the unit modal.
* Tweak: Set a max height on the builder metabox to allow better visibility.
* Tweak: Created an "Add Unit" button at the bottom of the units list in the builder.
* Tweak: The add new student courses field is no longer required.

= 4.4.1 =
* Fix: In some cases roles and capabilities specific to WP Courseware were not applied properly upon upgrade.
* Fix: Upon existing course upgrade it would sometimes duplicate the last course in the list and assign the same ID.

= 4.4.0 =
* New: New Course Builder! You can now build and configure your courses on one single page.
* New: New Course confirguration side tab layout.
* New: New course single page that consists of a featured image, course title, course description, enrollment button and course outline.
* New: Courses have been converted to a custom post type to allow for more flexibility when creating your online course.
* New: New re-configured course admin index page to display settings overview, shortcode example, and publish date.
* New: New Course category and tag taxonomies have been added to further distinguish your courses.
* New: Courses frontend index page has been converted to display as an archive page for better theming and customization.
* New: Courses now have 3 different options for a customized permalink structure.
* New: Course Units now have 3 different options for a customized permalink structure.
* New: Course category and tag taxonomy permalink base can be customized.
* New: Course Units category and tag taxonomy permalink base can be customized.
* New: Added wysiwyg's to the Course message and email editors.
* New: Added additional filters to the students admin list table so that table columns can be added and customized.
* New: Added additional filters and hooks to further extend the drip feed functionality.
* New: Added new wpcw_is_student_enrolled() core function.
* New: Added Course and Module as separate columns on the Units admin index page.
* New: Added Course and Module filter dropdowns to the Units admin index page.
* Tweak: Disabled the "Orders" and "Subscriptions" tabs on the student account page when all payment gateway(s) are disabled.
* Tweak: Removed the account billing fields on the student account page when all payment gateway(s) are disabled.
* Tweak: Removed the billing fields on the admin user profile page when all payment gateway(s) are disabled.
* Fix: Messaging issue with non-blocking quizzes which contained manually graded questions.
* Fix: Issue where specific post type is requested upon search resulting in incorrect template.

= 4.3.5 =
* New: Additional filters to change the certificate certify, completed, instructor, and date text.
* Tweak: Button and message styling for course unit and quizzes.
* Fix: In some cases the order recieved page and order failed page would not be created upon activation.
* Fix: When account page is not set and enroll button is clicked it displays a 404.
* Fix: On a new install license would not activate properly.

= 4.3.4 =
* New: Added css class "wpcw-checkout-payment-button" to the checkout button.
* New: Added additional transients to the "clear transients" utility in Tools.
* Tweak: Changed the 'N/A' language for course progress page to '-'.
* Fix: Resolved a conflict with the TCPDF library when used in other plugins.
* Fix: Perform validation before the Stripe checkout form appears.
* Fix: Perform enrollment when adding an Order manually.
* Fix: Enqueue frontend scripts in footer for better performance.
* Fix: Classroom search now redirects correctly when a search is performed.
* Fix: Quiz results / answers would not display on quiz completion in some cases.
* Fix: When the account page was set to home page the account endpoints would 404.
* Fix: User registration form does not set the username and password correctly.
* Fix: Lost password email did not contain correct information to reset password.
* Fix: An implemented filter on 'the_content' returned blank if outside the main query.

= 4.3.3 =
* New: Added GDPR features that are compatible with the upcoming WordPress 4.9.6 release.
* New: Added option to display a privacy checkbox on the checkout page.
* New: Added option to create a privacy page that is linked with the privacy checkbox.
* New: Added filters to change fonts on certificates.
* Fix: Enrollment button shortcode would not display the purchase button for a paid course.
* Fix: Remote images could not be used on a certificate.
* Fix: Upload image button for certificates on course edit page was not working.
* Fix: Course unit post type was set to be hierarchical and it should not be hierarchical.
* Fix: Stripe statement descriptor was passed as blank thus causing a Stripe error.
* Fix: Stripe checkout would display an error popup if an logo image was not set.

= 4.3.2 =
* Tweak: Tweaked billing fields on student profile page to not display if a payment gateway is enabled.
* Fix: Added backwards compatibility fix to the 'UserProgress' class that would cause fatal error if called from an external script, which also prevented upgrading to current version.
* Fix: Fixed issue where 'course_author' column was not created if upgrading from older version of WP Courseware.

= 4.3.1 =
* Tweak: Mark as completed button CSS was adjusted to compensate for a full width page.
* Fix: Fixed issue where page builders were attempting to render a shortcode on the admin side and triggered a fatal error.
* Fix: Fixed issue where enroll shortcode wasn't redirected to the WordPress registration form if the "Account" page wasn't created and set.
* Fix: Fixed issue where upgrading from a pre 4.0 version caused a database issue.

= 4.3.0 =
* New: New shopping cart, course listing and student account pages. Pages are setup automatcially upon activation!
* New: Courses can now be configured and offered for free, one time payment, or as subcriptions.
* New: Native PayPal Standard payment gateway integration complete with sandbox.
* New: Native Stripe & Stipe Checkout payment gateway integration complete with sandbox.
* New: Ability to issue refunds via API for both PayPal Standard & Stripe.
* New: Currency customization for payment gateways.
* New: Option to configure a flat tax rate.
* New: New customizable email notifications complete with merge tags for students related to new accounts, orders and subscriptions.
* New: New customizable email notifications complete with merge tags for administrators related to orders and subscriptions.
* New: Ability to customize email templates for new accounts, orders and subscriptions.
* New: Student account page displays courses, orders, subscriptions and profile information.
* New: New orders index page.
* New: New subscriptions index page.
* New: Ability to add multiple administrators to course email notificaitons.
* New: Added a utility that deletes orphaned tags that are at a "0" count.
* New: Added category and tag taxonomies to course units.
* New: Added two WordPress filter hooks to the enrollment shortcode in order to adjust language upon AJAX return.
* New: Added 6 new WordPress action hooks surrounding the next and previous buttons.
* New: Orders table added to the student profile admin page.
* New: Login form for student account page.
* Tweak: Namespaced the countdown timer script to avoid conflicts with other plugins and themes.
* Fix: Fixed issue where drip email notification was URL encoding the ampersand symbol in the subject and in the content.
* Fix: Fixed issue where images on quiz questions would not render on the quiz results PDF.
* Fix: Fixed issue where the ajax loader GIF wasn't working on the enrollment shortcode.
* Fix: Fixed issue where the filter by tag dropdown on the question pool lightbox wasn't filtering.
* Fix: Fixed issue where the [wpcourse_progress] shortcode wasn't displaying correctly on archive pages.
* Fix: Fixed issue where the course outline table appeared broken for users that were not logged in.
* Fix: Fixed issue where the student name would not appear correctly on the student index page if a user's display name was not set.
* Fix: Fixed issue where a question of type "random" was being displayed on the question pool table.
* Fix: Fixed bug where tag count was not updated if a tag was removed while adding a question.
* Fix: Fixed issue where the "add quiz" button on the module editor admin page didn't work.
* Fix: In some cases jQuery would be undefined on certain WP Courseware admin pages.

= 4.2.0 =
* New: Theme Customizer & Admin interface for customization of frontend ui colors.
* New: Student profile page.
* New: Newly designed add new/existing student page.
* New: Visual editor available for email messages.
* New: Bulk deletion menu for questions.
* New: Bulk enroll students form added to students page.
* New: Bulk enroll students form added to courses page.
* Tweak: Quizzes list table re-tooled to use wp list table.
* Tweak: Questions list table re-tooled to use wp list table.
* Tweak: Quick action navigation buttons for quizzes index and quiz edit page.
* Tweak: Quick action navigation buttons for quesitons index and quesiton edit page.
* Tweak: Unit list on module edit page are now links.
* Tweak: Image icons have been replaced with font icons.
* Fix: Permalink Notice when dismissed did not stay dismissed.
* Fix: Search issue on students page when name contained an apostrophe.
* Fix: Issue with certificate image urls resolving correctly on IIS servers.
* Fix: Plugin Admin Menu Editor compatibility.

= 4.1.3 =
* Fix: Plugin update process would fail in various cases.
* Fix: Affiliate url setting doesn't display the correct url.
* Fix: Sort by name on the students and classroom page sorted by ID instead of name.
* Tweak: Restored reset progress drop down on students index page.
* Tweak: Restored green student progress bars on students index page.
* Tweak: Reduced the size of the progress bars in the admin to be more compact.
* Tweak: Moved "View Detailed Progress" button to a link in the action menu displayed on hover under the students name.

= 4.1.2 =
* New: Unit drip schedule included in Course import/export.
* Fix: Instructor role capabilities on the course, course classroom, module, unit, and student pages.
* Fix: Translation file was being loaded from the wrong location.
* Fix: Conflict with S3 Media Maestro 3.0.5 and below.
* Fix: Quiz submission over forced SSL was not submitting.
* Fix: Classroom student search was redirecting to students page upon submission.
* Fix: Frontend enrollment success message displayed the course name incorrectly.
* Fix: Unit discussion metabox was not displaying even though comments were enabled on units.
* Fix: Individual student name was not being loaded in email modal when student email address was clicked.
* Fix: Compatability with Admin Menu Editor Pro.

= 4.1.1 =
* Fix: Plugin setup would cause a fatal error if plugin version is before 4.0.
* Fix: Minimum PHP version requirement changed from php 5.5.0 to php 5.4.0.

= 4.1.0 =
* New: Classroom page that displays students enrolled in a paticular class and allows you to manage them.
* New: Students index page that gives a birds eye view of all students enrolled into at least one course.
* New: Modules index page to manage modules.
* New: Restructured list tables for courses, course modules and course units.
* New: Restructured and condensed the main WP Courseware admin menu.
* New: New settings page that provides better functionality when configuring global settings and finding documentation.
* New: Enroll students directly from the classroom page.
* New: Enroll students directly from the "Add New User" screen.
* New: Email all students directly from the classroom page.
* New: Email individual students directly from the classroom & students page.
* New: Dropdown on classroom page to quickly swith to another classroom of another course.
* New: Ability to filter students by course on the students index page.
* New: Added navigation buttons to the top of each WP Courseware admin page to navigate more easily.
* New: RTL support for download quiz results PDF.
* Fix: Automatic enrollment to courses was being ignored when a user enrolled via shortcode.
* Fix: Fixed display issue with tags in the question pool and on a single question screen in WordPress 4.9.
* Fix: When a unit is duplicated the drip metabox settings were not configured properly.
* Fix: The enrollment shortcode was displaying an undefined variable when a user is logged in.
* Fix: The course units screen "filter by course" dropdown was displaying 'wp-courseware' for each list item.
* Fix: "Not Logged In" course access message was not displayed correctly on course progress page.
* Fix: Fixed php warning notice with enrollment shortcode.
* Tweak: Moved all student data from the users page to the new Students index page.

= 4.0.9.4 =
* Fix: Fixed a bug with email template tag that displays the site URL.
* Fix: Fixed a bug with the license renewal admin message.
* Fix: Fixed a bugs where quiz grade was rounding up causing a false positive on various admin screens.
* Fix: After submitting quiz and continuing to next unit, upon clicking back, the quiz is reset.
* Fix: TCPDF Error: can't get the size of the image.
* Fix: Remove content leak caused by a filter applied in the wrong place.
* Fix: MathJax was not loading correctly on page load.
* Fix: [wpcourse_progress] html table column alignment issue.
* Fix: Undefined php $status vaiable in activation process.
* Fix: Radio and Checkbox css alignment on quiz forms.
* Tweak: Added argument numbering to the message that shows the number of correct answers the user must get in order to pass in the admin screen for quizzes.
* Tweak: Additional fixes towards full compatability with WPML.
* New: Add function 'WPCW_getCourses' to allow users to get courses.
* New: Added a filter to the email merge tags so that custom merge tags can be added

= 4.0.9.3 =
* Fix: Fixed a bug with an admin message that was causing a PHP NOTICE

= 4.0.9.2 =
* Fix: Fixed a bug where the import function was not correctly importing the course unit author
* Fix: Fixed a few CSS issues when RTL languages are in use
* Tweak: Modified the enrollment shortcode function to have better messaging
* Tweak: Changed the_content filter to a priority of 20

= 4.0.9.1 =
* Fix: Fixed several PHP 7.1.x compatibility issues throughout the plugin
* Fix: Fixed an issue on the course progress page where the cumulative grade was using incomplete quizzes in the calculation
* Fix: Fixed an issue where possible answers were displaying the IDs of the answers rather than the answer text
* Fix: Fixed an issue where displaying possible answers was causing a PHP warning due to an illegal offset
* Tweak: Modified quiz grade to round up to the nearest whole number consistently throughout the admin and frontend

= 4.0.9 =
* Fix: Fixed bug where black line was appearing on the bottom of certificate
* Fix: Fixed issue with PHP compatibility 7.1
* Fix: Fixed issue where configuring comments with pagination would cause a 404 on course units (must re-save permalink structure)
* Fix: Fixed issue on quizzes with paging questions where clicking previous question wouldn't store the set of answers when using random answers

= 4.0.8 =
* Fix: Bug when using random answers on a quiz with paging in which the answer later button would not store "potential answers", allowing the user to click back and forth to a question determining the correct answer by process of elimination
* Fix: Fixed issue in localization template file where the keyword list was missing plural text strings
* Tweak: Updated the EDD Software Licensing Plugin Updater Class

= 4.0.7 =
* Fix: Bug where previous button would appear on question #1 of a retake causing further issues.
* Fix: Bug where certificate didn't work properly if WordPress was installed in a sub-folder.
* Fix: Bug where questions on paging quiz would skip if on PHP 5.x.

= 4.0.6 =
* Fix: Bug where text domain was missing and adjusted several text strings
* Fix: Bug where the option to display "all possible answers" was not including the "correct answer" in the list when listing them on the quiz results page
* Fix: Bug where time difference was not displaying properly on the user's quiz details page
* Fix: Bug where the answer later button wouldn't display on the first quetion of a quiz retake
* Fix: Bug where the answer later button was not redirecting to the correct question
* Fix: Bug where a missing parameter in function call was causing notices to display in error log
* Fix: Bug where the random answers function was not functioning properly when using a random question selection
* Tweak: Updated the EDD Software Licensing Plugin Updater Class

= 4.0.5 =
* Fix: Bug where adding a new answer would not save correctly
* Fix: Bug where randomized answers on multiple choice questions didn't render consistently when using a paging quiz
* Fix: Bug when adding an individual to question pool was not error checking correctly
* Fix: Bug where the open ended survey question didn't have option for text box size
* Fix: Fixed a few text domain strings that didn't get changed with version 4.0.3
* Fix: Fixed issue where units and quiz data was not updated correctly when a module was reassigned to another courses
* Tweak: Course unit templates are included in export/import

= 4.0.4 =
* New: Support added for WP Courseware Note addon plugin by WisdmLabs
* Fix: Fixed bug when adding an answer to a multiple choice question would automatically select it as the correct answer
* Tweak: Added checking for duplicate answers in multiple choice questions

= 4.0.3 =
* New: Built in MathJax support for quiz questions
* New: Shortcodes are now supported in quiz questions, however, shortcodes must NOT be loaded via JavaScript
* Fix: Bug where unassigned unit was showing an error on the front end due to not being assigned
* Fix: If using a non-blocking quiz, the grade was not displaying correct data on the "Detailed User Quiz/Survey Results" page
* Fix: Fixed bug that prevented email templates from being localized
* Tweak: Changed text domain from wp_courseware to wp-courseware. There is backward compatibility for the legacy wp_courseware text domain. Language files can be kept in "wp-content/languages/wp_courseware/" or "wp-content/languages/plugins/wp-courseware/"

= 4.0.2 =
* Fix: Fixed issue where the option for quiz attempts would not show up when configuring a blocking quiz
* Fix: Fixed a minor issue with enrollment that would trigger database errors if no course data was passed into the query

= 4.0.1 =
* Fix: Fixed bug where grade book CSV was not downloadable
* Fix: Fixed bug where some quiz questions couldn't be deleted out of a quiz
* Fix: Fixed bug where plugin was not communicating with our license server and displaying an "Exceeded license limit" error

= 4.0 =
* New: Added instructor role
* New: Instructor role create/edit/delete course capability
* New: Instructor role create/edit/delete module capability
* New: Instructor role create/edit/delete unit capability
* New: Instructor role create/edit/delete quiz/survey capability
* New: Instructor role create/edit/delete quiz questions capability
* New: Instructor role create/edit/delete quiz question tags capability
* New: Instructor role create new user
* New: Instructor role can enroll/de-enroll user from course and update course progress
* New: Certificates can now be configured per course
* Tweak: Units support post password protected content
* Tweak: Updated the data_quiz column in the wpcw_user_progress_quizzes table to use LONGTEXT
* Tweak: Restructured certificates to be executed by function opposed to called directly by file
* Fix: Fixed issue when deleting modules containing units/quizzes would place units in an unassigned state with the quiz still attached
* Fix: Download Certificate button opens certificate in new window
* Fix: Fixed issue where download results setting was not working correctly
* Fix: File upload question type does not require a lower case file extension
* Fix: Fixed issue where not all user messages were being exported correctly

= 3.9.0 =
* New: Course prerequisites
* New: Bulk question import
* New: Single question input option directly to question pool
* New: Added filter to course units screen to filter by course
* Tweak: Download Results PDF in new tab
* Fix: Fixed a bug where unit would get marked as completed unexpectedly
* Fix: Updated a call to construct that was causing an error on admin screens that contained tables

= 3.8.5 =
* New: Multiple choice question now has multiple answer option
* New: Added CSS ’active’ element status to course progress widget for better styling ability
* Fix: Fixed a bug where quiz question answers were not randomizing correcly
* Tweak: Added function that will check for a completed quiz sitting on top of an uncompleted unit
* Tweak: Updated constructors to be compliant with PHP7
* Tweak: Added screen options to Course Dashboard Page

= 3.8.4 =
* New: Added two new email template tags for {FIRST_NAME} and {LAST_NAME}
* New: Added new filter wpcw_front_completion_box to filter content between the course unit loop content and the navigation buttons
* New: Added an option to hide the Download Results button
* New: Added an option to enable/disable the ability to turn on comments for course units
* New: Added a new shortcode for course enrollment buttons
* New: Added a search and paging function to the course dashboard
* Tweak: Changed certificate button to open certificate in new window
* Fix: Fixed minor bug with question tag maintenance routine
* Fix: Fixed a minor bug with AJAX call over HTTPS that would not allow course units to be completed
* Fix: Fixed minor issue where certain text strings with a particular keyword were not being included into the language file

= 3.8.3 =
* New: Added shortcode parameter "user_quiz_grade" to the [wpcourse_progress] and [wpcourse] shortcodes to display quiz grades
* New: Added shortcode parameter "certificate" to the [wpcourse_progress] to display certificate
* New: Made certificate button available on the user progress page
* New: Added course completion date column to the grade book
* New: Added course completion date to the grade book export CSV
* Fix: Issue where duplicate notifications were sent out upon course completion when the last unit contained a quiz that required manual grading
* Fix: Bulk user progress reset was not working
* Tweak: Changed the priority output of the column/row elements displaying on the users page
* Tweak: Changed the color of the "Delete Course" and "Delete Module" buttons

= 3.8.2 =
* Fix: Fixed issue where progress percentage was being completely reset upon resetting students progress to an earlier module/unit

= 3.8.1 =
* Fix: Fixed issue with "All units visible" not displaying the "next button" on course units
* Fix: Fixed issue with non-blocking quizzes in which the quiz results message and custom feedback messages would remain persistent upon retaking a quiz
* Fix: Fixed issue with blocking quiz custom feedback messages in which "failed" messages would not to appear

= 3.8.0 =
* New: Drip feed functionality. Drip feed based on interval from course enrollment date or drip feed based on calendar date
* New: Customizable drip feed email notification
* New: Custom enrollment date configuration option
* New: Tag query has been improved to be much more efficient. Thanks to Andy Adams @andyonsoftware http://www.certainlysoftware.com/
* Tweak: Next/Previous button code improved to be more efficient
* Tweak: When sending a unit in to the trash, the quiz will disassociate frmo the unit
* Fix: When a unit is sent to trash, the course order will be saved at that point to prevent a 404 from occurring in the course
* Fix: Adjusted widget code to be compliant with WordPress 4.3
* Fix: When a users progress is reset, the certificate is removed and the final grade notification sent flag is removed
* Fix: Module Number email tag is now working on respective email notifications

= 3.7.0 =
* Fix: Fixed bug where quiz timer wouldn't start if max attempts were reached for quiz and an additional retake was allowed

= 3.6.0 =
* Fix: Fixed bug where deleting question from pool would not delete associated tag
* New: Added function to clean up orphaned tags upon plugin activation

= 3.5.0 =
* Fix: Fixed bug with user email notification posting incorrect grade after quiz was manually graded
* Fix: Fixed bug where duplicate email notifications were sent out if a manually graded quiz was placed on the last unit of the course

= 3.4.0 =
* Fix: Fixed bug with quiz messaging
* Fix: Fixed bug where the unit of non blocking quiz was not being being flagged as completed upon submitting quiz causing course not to complete
* Fix: Fixed issue where student’s answers for open ended quiz questions were not showing line breaks (carriage returns) on post

= 3.3.0 =
* Fix: Fixed bug with filter when adding a quiz questions from pool into quiz
* Fix: Fixed bug where a quiz could be dragged/dropped onto an unassigned unit
* Tweak: Certificate to be UTF-8 removing the options for specific encoding
* New: Added 2 more fonts for certificates and quiz results and removed Helvetica which didn’t work well with other languages
* New: Added support for line breaks (by using carriage return) with in quiz questions while leaving ability to have html tags as part of a question

= 3.2.0 =
* Fix: Fixed issue where clicking "mark as completed" was displaying even if unit was complete
* Fix: Fixed issue with multiple choice question answer randomization
* Fix: Fixed issue with adding an image to a quiz question answer while working directly in the question pool
* Fix: Fixed issue with adding/removing answer from a multiple-choice question while working directly in the question pool

= 3.1.0 =
* New: Added option to provide a recommended guideline score for non-blocking quizzes
* New: Added support for timed quizzes when in non-blocking quiz mode
* New: Added support for setting a retake limit for non-blocking quiz mode
* New: Added a new option in the quiz results settings which allows for the display of all possible answers in addition to the user's answer and the correct answer
* Tweak: Added email address support for new TLDs
* Fix: Database issue with adding quiz questions
* Fix: Issue with handling user course deletion

= 3.0.0 =
* New: Quiz question pool to allow for recycling of questions in multiple quizzes
* New: Support for randomizing quiz questions or manually adding specific questions from question pool
* New: Support for randomized answers within multiple choice questions
* New: Option for timed quizzes
* New: Support for quiz attempt limits with manual override capabilities for instructors
* New: Custom feedback messages which provide feedback by quiz topical sections
* New: Option to tag quiz questions for use in randomizing questions by topical section and providing automated feedback messages
* New: Option for students to download quiz results as a PDF
* New: Multiple options for paginating quiz questions
* New: Redesigned and enhanced quiz/survey creation UI
* New: Improved question addition UI for quizzes
* New: Several new email template tags for sending quiz result details to students

= 2.9.0 =
* New: Leave survey responses available for later
* New: Delete the entire course and its contents or retain units and quizzes
* Tweak: Support for quizzes when exporting and importing courses

= 2.8.0 =
* New: Encoding support for the certificate
* New: Export survey results
* New: Added new hooks/filters
* Fix: Addressed various strings that were missing a text domain
* Fix: Several bugs in relation to the database

= 2.7.0 =
* New: Custom templates capability
* New: Sort courses by ID
* New: Sort courses title
* New: Sort quizzes by ID
* New: Sort quizzes by title
* New: Duplicate course units
* Tweak: No Answers" quiz option to not indicate which answers were correct/incorrect

= 2.6.2 =
* Fix: Quiz calculation bug

= 2.6.1 =
* Fix: Quiz database bug error

= 2.6.0 =
* New: Ability to show correct answer in quiz
* New: Ability to show users answer in quiz
* New: Show explanation in quiz
* New: Mark answers correct/incorrect in quiz
* New: Leave quiz results available for later viewing
* Fix: Quotes in quiz question issue
* Fix: True false question with regard to accessibility in clicking the label to select an answer
* Fix: Grade book export file name to a more appropriate name
* Fix: Shortcode for the progress ID greater than "9"
* Fix: Ability to expand/contract all modules when adding a WPCW Course Progress widget with a specific class to any page or post

= 2.5.1 =
* New: Modified video documentation

= 2.5.0 =
* New: Global and individual student course reset functions
* New: Global enrollment button for new courses (including admins)
* New: Ability to add images to all quiz questions and answers
* New: Shortcode function for dynamic course outline complete with user progress bar and cumulative grade

= 2.4.0 =
* New: Grade book function
* New: Open ended question (with short, medium and large boxes for answers and hints)
* New: Upload question (with file filters)
* New: New email notifications for grade book
* New: Organized course settings page
* New: Various notifications for instructor to input manual grades for open ended questions and upload submission
* New: Exportable grade book
* Fix: Dynamic sidebar widget issue that would cause the sidebars in the WordPress admin panel to disappear

= 2.3.2 =
* New: Dynamic sidebar widget
* Fix: Allow an imported course to be registered by an enrolled user
* Fix: Delete a multiple choice quiz question if the answer was set to "0"

= 2.3.1 =
* New: Additional localization areas
* Fix: "Force Table Upgrade" bug that didn't properly update all tables
* Fix: Certificate availability if last unit contained quiz or survey
* Fix: Module list bug on the student progress page to list correct module number
* Fix: Import users bug which added additional mime types for Microsoft Office(TM) users

= 2.3.0 =
* New: Bulk user import function with template (CSV) file included
* New: Certificate feature allowing a user to download a custom certificate upon course completion
* New: Localization enhancements with default template (POT) file included
* New: Functions added to support add-on integration with multiple membership plugins
* Fix: Apostrophe bug that created backslashes in a quiz questions
* Fix: FireFox bug that didn't allow you to add questions in a quiz
* Fix: Unassigned units and unassigned quizzes overflow
* Fix: Search for plugin bug showing empty details area in lightbox
* Fix: MySQL strict mode bug that would cause MySQL errors if MySQL was run in strict mode

= 2.2.0 =
* Fix: Bug that prevented WP Courseware from receiving future updates

= 2.1.0 =
* Fix: Bug that stopped you being able to add a question if your WordPress database table prefix was something other than wp_

= 2.0.0 =
* New: Quiz/Survey functionality
* New: "Powered by WP Courseware" link which utilizes ClickBank for affiliate type commissions
* Fix: 404 error bug  Added Next/Previous navigational buttons to course units

= 1.1.0 =
* New: additional documentation

= 1.0.0 =
* Base Plugin Release
