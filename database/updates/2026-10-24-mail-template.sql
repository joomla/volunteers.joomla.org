-- Add the mail template of the "E-mail current selection" button of the members list.
-- The subject and the message are written in the contact form, the template only adds them to the mail.
INSERT IGNORE INTO `#__mail_templates` (`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`)
VALUES ('com_volunteers.members.mail', 'com_volunteers', '', 'COM_VOLUNTEERS_MAIL_MEMBERS_MAIL_SUBJECT', 'COM_VOLUNTEERS_MAIL_MEMBERS_MAIL_BODY', 'COM_VOLUNTEERS_MAIL_MEMBERS_MAIL_HTMLBODY', '', '{"tags":["subject","body"]}');

-- Send mail templates as plain text and HTML, so the HTML message of the editor is kept
UPDATE `#__extensions`
   SET `params` = JSON_SET(IF(`params` IN ('', '{}'), '{}', `params`), '$.mail_style', 'both')
 WHERE `type` = 'component' AND `element` = 'com_mails';
