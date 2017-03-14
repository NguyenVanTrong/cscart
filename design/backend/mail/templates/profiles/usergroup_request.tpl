{include file="common/letter_header.tpl"}

{__("text_usergroup_request")}<br>
<p>
<table>
<tr>
    <td>{__("usergroup")}:</td>
    <td><b>{$usergroup}</b></td>
</tr>
<tr>
    <td>{__("person_name")}:</td>
    <td>{$user_data.firstname}&nbsp;{$user_data.lastname}</td>
</tr>
<tr>
    <td>{__("email")}:</td>
    <td>{$user_data.email}</td>
</tr>
</table>
</p>
{include file="common/letter_footer.tpl" user_type='A'}