<div class="panel">
    <div class="panel-heading">
        <i class="icon-credit-card"></i> {l s='PayPlus Transactions' mod='payplus'}
    </div>

    {if isset($transactions) && count($transactions) > 0}
        <table class="table">
            <thead>
                <tr>
                    <th>{l s='Order Reference' mod='payplus'}</th>
                    <th>{l s='Amount' mod='payplus'}</th>
                    <th>{l s='Currency' mod='payplus'}</th>
                    <th>{l s='Status' mod='payplus'}</th>
                    <th>{l s='Session ID' mod='payplus'}</th>
                    <th>{l s='Created' mod='payplus'}</th>
                    <th>{l s='Updated' mod='payplus'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$transactions item=transaction}
                    <tr>
                        <td>{$transaction.order_reference}</td>
                        <td>{$transaction.amount} {$transaction.currency}</td>
                        <td>{$transaction.currency}</td>
                        <td>
                            <span class="badge {if $transaction.status == 'completed'}badge-success{elseif $transaction.status == 'pending'}badge-warning{elseif $transaction.status == 'failed'}badge-danger{else}badge-secondary{/if}">
                                {$transaction.status}
                            </span>
                        </td>
                        <td><code>{if $transaction.session_id}{$transaction.session_id}{else}-{/if}</code></td>
                        <td>{$transaction.created_at}</td>
                        <td>{$transaction.updated_at}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <div class="alert alert-info">
            {l s='No transactions found.' mod='payplus'}
        </div>
    {/if}
</div>

<style>
.badge {
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.badge-success {
    background-color: #28a745;
    color: white;
}

.badge-warning {
    background-color: #ffc107;
    color: black;
}

.badge-danger {
    background-color: #dc3545;
    color: white;
}

.badge-secondary {
    background-color: #6c757d;
    color: white;
}

code {
    background-color: #f5f5f5;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 12px;
}
</style>
