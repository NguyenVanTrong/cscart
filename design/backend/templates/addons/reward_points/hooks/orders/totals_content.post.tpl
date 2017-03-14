{if $order_info.points_info.reward}
    <tr>
        <td>{__("points")}:</td>
        <td>{__("points_lowercase", [$order_info.points_info.reward])}</td>
    </tr>
{/if}

{if $order_info.points_info.in_use}
    <tr>
        <td>{__("points_in_use")}&nbsp;({__("points_lowercase", [$order_info.points_info.in_use.points])}):</td>
        <td>{include file="common/price.tpl" value=$order_info.points_info.in_use.cost}</td>
    </tr>
{/if}