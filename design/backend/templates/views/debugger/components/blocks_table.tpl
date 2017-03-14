<table class="deb-table">
    <thead>
    <tr>
        <th>Block ID</th>
        <th>Block type</th>
        <th>Block name</th>
        <th>Performance</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$blocks item="block" key="block_id"}
        <tr>
            <td>{$block_id}</td>
            <td>{$block.block.type}</td>
            <td>{$block.block.name}</td>
            <td>
                <table class="deb_table">
                    <thead>
                    <tr>
                        <th>Time</th>
                        <th>Memory</th>
                        <th>DB queries</th>
                        <th>Included files</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{$block.render_performance.total.time|number_format:"5"}</td>
                        <td>{($block.render_performance.total.memory/(1024*1024))|round:"2"} MB</td>
                        <td>{$block.render_performance.total.queries}</td>
                        <td>{$block.render_performance.total.included_files}</td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>
