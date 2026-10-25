-- Update the Frequently Asked Questions article (/faq) for Joomla 6.
-- Joomlers register and edit their profile on the Joomla Identity portal, the team pages use the
-- current button and tab names, and the Glip chat channel is gone.

UPDATE `#__content`
   SET `introtext` = '<h3>Joomlers/Accounts</h3>
<p><strong>Q: What is a "Joomler"?</strong></p>
<p>A: Joomlers are volunteers that contribute to the Joomla project.</p>
<p><strong>Q: How do I become a Joomler?</strong></p>
<p>A: Create a <a href="https://identity.joomla.org/register">Joomla Identity</a> and provide the consents for the Joomla Volunteers Portal on the <a href="https://identity.joomla.org/consents">consents page</a>. Your Joomler profile then becomes visible on this site. See <a href="register">Register</a> for more information.</p>
<p><strong>Q: How can I edit my Joomler profile?</strong></p>
<p>A: Your Joomler profile comes from your Joomla Identity. Log in, go to your own Joomler profile and click on the "<em>Edit My Joomler Profile</em>" button at the top right. This opens your profile on <a href="https://identity.joomla.org/">identity.joomla.org</a>, the changes show up on this site after you save them there.</p>
<hr />
<h3>Teams</h3>
<p><strong>Q: My team is missing. Can it be added?</strong></p>
<p>A: Yes, please contact the Department responsible for your team and ask them to create your team.</p>
<p><strong>Q: How can we edit the Team information?</strong></p>
<p>A: Team Leaders and Department Coordinators can edit the team information by clicking on the "<em>Edit Team</em>" button at the top right of the team page.</p>
<hr />
<h3>Team Members</h3>
<p><strong>Q: I am a member of a team, how can I be listed as team member?</strong></p>
<p>A: Make sure you are <a href="register">registered</a> as a Joomler. Then ask your Team Leader or Department Coordinator to add you to the members list of the team.</p>
<p><strong>Q: Who can manage the team members?</strong></p>
<p>A: Team Leaders and Department Coordinators can manage the members of a team with the "<em>Add Joomler</em>" button on the Members tab of the team page.</p>
<p><strong>Q: I want to add a person to a team, but they are not in the list of Joomlers</strong></p>
<p>A: In that case the person is not a Joomler yet. Ask them to <a href="register">register</a>, after that you can add them to the team.</p>
<p><strong>Q: A team member leaves the team, how can I remove the team member from the members list?</strong></p>
<p>A: Edit the member of your team and set the "<em>Date Ended</em>". The Joomler is then moved from the Members tab to the "<em>Honour Roll</em>" tab.</p>
<hr />
<h3>General</h3>
<p><strong>Q: Where can I report bugs &amp; feature requests?</strong></p>
<p>A: The Volunteers Portal code is published on GitHub for community contributions: <a href="https://github.com/joomla/volunteers.joomla.org">https://github.com/joomla/volunteers.joomla.org</a>. You can report bugs and feature requests in the issue tracker: <a href="https://github.com/joomla/volunteers.joomla.org/issues">https://github.com/joomla/volunteers.joomla.org/issues</a>.</p>
<p><strong>Q: I want to help to improve the Volunteers Portal, how can I join?</strong></p>
<p>A: We are always looking for team members to join the <a href="teams/volunteers-portal-team">Volunteers Portal Team</a>.</p>',
       `modified` = UTC_TIMESTAMP()
 WHERE `alias` = 'frequently-asked-questions-and-answers';
