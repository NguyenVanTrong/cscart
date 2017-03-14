<!DOCTYPE html>
<html dir="{$language_direction}">
<head>
{literal}
<style type="text/css" media="screen,print">
body {
    padding: 0;
    margin: 0;
    text-align: center;
}
a, a:link, a:visited, a:hover, a:active {
    color: #000000;
    text-decoration: underline;
}
a:hover {
    text-decoration: none;
}

#print-wrapp {
	max-width: 800px;
	width: 100%;
	margin: 0px auto;
	text-align: initial;
}
</style>
{/literal}
</head>

<body>
{include file="common/scripts.tpl"}
<table id="print-wrapp">
	<tr>
		<td>
			{$content nofilter}
		</td>
	</tr>
</table>
</body>
</html>