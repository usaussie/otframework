{if count($messages) != 0}
<div class="messageContainer">
    <div class="message">
    {foreach from=$messages item=m}
    {$m}<br />
    {/foreach}
    </div>
</div>
{/if}
{editable id="selectHelper"}
Select the type of action you would like to trigger.
{/editable}
<br /><br />
{$form}
<br /><br />
    <b>Available Variables</b><br /><br />
    {editable id="availableVars"}These variables are available for inclusion
    in your scripts.  The placeholders will be processed with actual data when
    a trigger is executed.
    {/editable}
    <br /><br />
    <table class="list sortable">
    <tbody>
        <tr>
            <th width="200">Variable</th>
            <th width="300">Description</th>
        </tr>
        {foreach from=$templateVars item=t key=k}
        <tr>
            <td>[[{$k}]]</td>
            <td>{$t}</td>
        </tr>
        {foreachelse}
        <tr>
            <td colspan="2">No variables available</td>
        </tr>
        {/foreach}
        </tbody>
    </table>