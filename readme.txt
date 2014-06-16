=== DW Question & Answer ===
Contributors: designwall, Farid-Gh, scheunemann, gciprian, Ahmet Kolcu, Astrotenko Roman, David Robles, Nidhal Naji, developez, markhall1971
Tags: question, answer, support, quora, stackoverflow
Requires at least: 3.0.1
Tested up to: 3.9.1
Stable tag: 1.2.9
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Your WordPress site will have a full-featured Question & Answer section like Stack Overflow, Quora or Yahoo Answers

== Description ==

DW Question and Answer is a WordPress plugin which builds a complete Question & Answer system for your WordPress site, like Quora or Stackoverflow. The plugin supports multi-languages, shortcodes, reCAPTCHA, email notification system and so on.

= Key features: =
* Submit / Filter / Order / Edit / Delete Question
* Answer / Comment 
* Vote and Pick Best Answer
* Notification Email system
* Instant search by keywords
* 11+ languages supported
* reCAPTCHA supported
* Shortcodes available
* Private/ Public for Question and Answer
* Questions / Answers follow function
* Sticky Question
* More to come

= Add-on for the plugin: =
* Embed question and Social Sharing: [http://wordpress.org/plugins/dw-question-answer-embed-question/](http://wordpress.org/plugins/dw-question-answer-embed-question/)

= Documents and Support: =
You can find [Documents](http://www.designwall.com/guide/dw-question-answer-plugin/) and more detailed information about DW Question and Answer plugin on [DesignWall.com](http://www.designwall.com/). 
We provide support both on support forum on WordPress.org and our [support page](http://www.designwall.com/question/) on DesignWall.

= Languages supported: =
* English (default) 
* Arabic (ar_AR) - by Nidhal Naji
* Chinese (zh_CN) - by Jack Cai
* French (fr_FR) - by [Kanzari Haithem](http://www.designwall.com/profile/kanzari/)
* German (de_DE) - by [scheunemann](https://github.com/scheunemann)
* Indonesian (id_ID) - by Ruby Aperta
* Persian (fa_IR) - by [Farid-Gh](https://github.com/Farid-Gh)
* Polish (pl_PL) - by Karol Pergot
* Romanian (ro_RO) - by [gciprian](https://github.com/gciprian)
* Russian (ru_RU) - by [Roman Astrotenko](http://www.designwall.com/profile/shtirlitz/)
* Spanish (es_ES) - by David Robles, [Developez](https://github.com/developez)
* Turkish (tr_TR) - by Ahmet Kolcu
* Thai (th) - by [Varut Vutipongsatorn](http://www.arika.co/questions)
* Hindi (hi_IN) - by [Gaurav Tiwari](http://gauravtiwari.org)
* Catalan (ca) - by [dactil](http://www.dactil.net/sag/)
* Vietnamese (vi_VN) - by [Le Nghia](http://www.designwall.com/profile/delatdecatsini/)
* Czech ( cs_CZ ) - by [Karel Baláč](karel.balac@gmail.com)
* Italian ( it_IT ) - by [Alberto Lusoli](alberto.lusoli@gmail.com)

The plugin is frequently updated and more and more features added based on all the feedback from our users. This means you are welcome to give us feedback and suggestion on how you would want to have in the plugin.
Visit our [Github](https://github.com/designwall/dw-question-answer) project or follow us at [@designwall_com](https://twitter.com/designwall_com) to get update of our next release.

[youtube http://www.youtube.com/watch?v=usS9ug0pI7A]

== Installation ==

1. Upload `dw-question-answer` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Log In to your WordPress Dashboard and go to menu `Dashboard > DW Q&A > Settings` then choose pages where to put submit question form and list questions page.

== Screenshots ==

1. Front-end appearance
2. Ask question page
3. Single question page
4. Search page with Instant search function
5. Back-end settings

== Changelog ==

= 1.2.9 =

* Fix: Link to shortcode page does not overwrite the 404 page
* Fix: Submit form content lost when providing error captcha
* Fix: Recaptcha conflict
* Fix: unauthorized users can edit questions/answers from back-end
* New: Update single question UI


= 1.2.8 =
* Order questions by latest answer post
* Support custom user's roles
* Czech Languages Supported
* Italian Languages Supported
* Fix bugs

= 1.2.6 =
* New: Allow Anonymous post question
* New: Allow Review new questions before publishing feature
* New: Thai language supported
* New: Hindi language supported
* New: Catalan language supported
* New: Vietnamese language supported
* Update: Update default language file

= 1.2.5 =
* New: Arabic supported
* New: Chinese supported
* New: Polish Supported
* New: Indonesian Supported
* Fix: Some text in template-functions.php was enabled to translate
* Fix: Remove error in add_cap function when active plugin
* Fix: update navigation
* Fix: js in shortcode
* Fix: error when delete question
* Update: dwqa-es_ES.po

= 1.2.4 =
* New:  Spanish Languages supported
* New:  Russian Languages supported
* New:  French Languages supported
* Update: Editor Update for Wordpress 3.9
* Fix: Email header was lost when have from field

= 1.2.3 =
* New:  Turkish Languages supported
* New: Add new function Edit/Delete Question in Front-end
* New: Add permission settings for Edit/Delete question in back-end.
* Fix:  Just add Insert Code button in the editor area inside the DWQA's Pages

= 1.2.2 =
* Fix: Recover shortcodes
   		'dwqa-popular-questions',
        'dwqa-latest-answers',
        'dwqa-question-followers'
= 1.2.1 =
* New : Re-design question status icons
* New : German language supported
* New : Add setting to enable / disable private question
* New : Add email settings for admin email notification (edit/ change emails to receive notification)
* New : Setting:  Send A Copy Of Every Email To Admin
* Fix : email template
* Update : languages file

= 1.2.0 =
* New : Sticky Questions
* New : Shortcode For Popular Questions
* New : Shortcode For Popular Latest Answers
* New : Shortcode For Question List
* New : Shortcode For Ask Question Form
* New : Questions per page Settings
* New : Language: Persian Language supported
* Fix : Duplicate in follow function
* Fix : The visible of the best answer in question single page
* Update: THESIS theme Compatible

= 1.1.1 =
* Fixed: Questions are not followed automatically if answer authors post private answers
* Fixed: Followers do not receive the email notification when there is a new comment to question
* Fixed: Admin does not receive the email notification when there is a private question
* Fixed: Question Author does not receive the email notification when there is a private answer.
* Fixed: Question author does not receive the email notification when there is an anonymous post
* New: Add Captcha System.
* New: Add 3 email notifications: New Answer to followed question, New Comment to Question (followers), New Comment to Answer (followers)
* Tweak: Sidebar is back with supported widgets.

= 1.1 =
* Fixed: Don't automatically pick the best answer which has the most votes ( at least 3 votes)
* Fixed: Only admin and author's question can read the best answer
* Fixed: Can still add answer comment for closed questions
* Fixed: Display number of answers incorrectly
* Fixed: Link format in comment box displays incorrectly after editing
* Fixed: After following the question, will change the tooltip to "Unfollow this question"
* Fixed: Draft answers publish automatically when change status of the draft answers
* Fixed: Missing avatar of anonymous after posting comment
* Fixed: Subscriber can change private/public questions of other people
* Fixed: Anonymous can follow the question
* Fixed: Private question owners can not read their own private answers
* Fixed: Answers disappear after answer author changes status from public to private
* Fixed: Permalinks don't displays properly as in back-end settings
* Fixed: Ordered by bulleted list and numbered list don't display properly after posting answers
* Fixed: Still show "Edit/delete" icon on question comment after disabling "edit" comment
* Fixed: Tags filtering displays the results incorrectly
* Fixed: Anonymous can not post comments after enabling  anonymous to post the comments
* Fixed: Permalinks don't work properly after refreshing
* Tweak: Missing "flag" function at front-end after disabling "edit"/"delete" answer in back-end
* Tweak: Not highlight "questions" page on the menu when viewing a single question
* New: Filter Questions which have new comments
* New: New user interface
* New: Add option to enable/disable notification email in back-end
* New: Add registering form
* New: Follow/Unfollow questions
* New: Switch question/answer between Private and Public

= 1.0.4 =
* Fixed: Can not publish Private question.
* Fixed: Link format in question comment box does not display properly.
* Fixed: Replace text "by by" under the question with " by -question author"
* New: Use new vector-based icon for DW Q&A Menu 

= 1.0.3 =

* Fixed: Missing attribute "class" when insert codes to <code> tag on Answer Editor
* Fixed: Input's placeholder disappeared on IE 8,9 in submit question page
* Fixed: Time is incorrect when add question/answer/comment
* Fixed: duplicate answer after changing status of the question
* Fixed: Line spacing between code lines becomes larger after editting
* Fixed: Can not post comment on IE 8
* Fixed: Time stamp is overlapped by avatar

* Tweak: Auto create 2 pages: "Questions" & "Ask" when active plugin
* Convert links when add new comment
* Have a message to inform number of charaters for title box

* New: Permission Settings - allow you to set permissions for default user roles: read, post, edit and delete either questions, answers or comments

= 1.0.2 =

* Tweak: When user add a new comment/answer, status of question is changed to "open".
* Fixed: Do not press "enter" key to post new comment.
* Fixed: The answer cloned automatically after changing question status.
* Fixed: When flag an answer, the answer should be automatically hidden.
* Fixed: Function to Show/Hide an answer after flagging the answer works incorrectly.
* Fixed: Question link and "View Comment" button don't work on new comment to question notification email.
* Fixed: Can pick best answer for a draft answer.
* Fixed: Filter functions don't work on IE8.
* Fixed: Related questions were not being displayed by related Tags & Categories.
* Fixed: Timestamp of the comment in the single post is incorrect after activating DW QA plugin.
* Fixed: Questions don't appear on IE9.
* New: Ready to translate into your native language. 


= 1.0 =

* The first version of DW Question & Answer
