<!-- ******* archive ******** -->	
<table border="0" cellpadding="5" cellspacing="0" width="400">
  <tr> 
    <td valign="top">
{{ unset_section }}
{{ list_issues length="8" order="bydate desc" }}
  {{ if $campsite->issue->is_current }}
  {{ else }} 
  <tr>
    <td valign="top">
      <p class="blok-naslov"><b><a class="blok-naslov" href="{{ uripath options="issue" }}">{{ $campsite->issue->name }}, dated {{ $campsite->issue->date|camp_date_format:"%W, %M %Y" }}</b></a></p>
      {{ local }}
      {{ set_publication name="Dynamic" }}
      {{ unset_section }}
      {{ list_articles }}

        <span class="dalje"><a class="dalje" href="{{ uri options="reset_subtitle_list" }}">{{ $campsite->article->name }}</a></span><br/>

      {{ /list_articles }}                          
      {{ /local }}
    </td>
  </tr>
			  
  {{ if $campsite->current_list->at_end }}

  <!-- forward / back -->

  <tr>
    <td align="center">
      <table border="0" cellpadding="0" cellspacing="0" width="200">
	<tr>
	  <td colspan="3"> </td>
	</tr>
	<tr>
	  <td align="center" width="99">

          {{ if $campsite->current_list->has_previous_elements }}

            <a href="{{ uri options="template archive.tpl" }}"><img src="/templates/img/nazad.gif" border="0"></a>

          {{ else }}
          {{ /if }}
          
        </td>
        <td width="2"> </td>
	<td align="center" width="99">
        
          {{ if $campsite->current_list->has_next_elements }}

          <a href="{{ uri options="next_items" options="template archive.tpl" }}"><img src="/templates/img/napred.gif" border="0"></a>

          {{ else }}                                     
          {{ /if }}
        </td>
      </tr>
    </table>
  </td>
</tr>

{{ /if }}

{{ /if }}
{{ /list_issues }}                
				  
<!-- ********* end arhiva *********** -->
    </td>
  </tr>
				  
</table>