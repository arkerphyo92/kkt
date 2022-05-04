<script type="text/x-template" id="wpcw-form-row">
    <tr valign="top" class="wpcw-form-row" :class="rowClasses">
        <th scope="row" valign="top" class="label-cell">
            <div class="label-cell-content">
                <slot name="label"></slot>
            </div>
        </th>
        <td scope="row" valign="top" class="input-cell">
            <div class="input-cell-content">
                <slot name="input"></slot>
            </div>
        </td>
    </tr>
</script>