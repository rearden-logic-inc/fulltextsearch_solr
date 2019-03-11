/*
 * FullTextSearch_ElasticSearch - Use Elasticsearch to index the content of your nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/** global: OC */
/** global: solr_elements */
/** global: fts_admin_settings */

// Simple controller for handling the saving and retrieving of the settings from the next cloud server.
var solr_settings = {

	settings_url: '/apps/fulltextsearch_solr/admin/settings',

	refreshSettingPage: function () {

		$.ajax({
			method: 'GET',
			url: OC.generateUrl(solr_settings.settings_url)
		}).done(function (res) {
			solr_settings.updateSettingPage(res);
		});

	},

	/** @namespace result.solr_host */
	/** @namespace result.solr_index */
	/** @namespace result.analyzer_tokenizer */
	updateSettingPage: function (result) {

		solr_elements.solr_host.val(result.solr_host);
		solr_elements.solr_index.val(result.solr_index);
		solr_elements.analyzer_tokenizer.val(result.analyzer_tokenizer);

		fts_admin_settings.tagSettingsAsSaved(solr_elements.solr_div);
	},


	saveSettings: function () {

		var data = {
			solr_host: solr_elements.solr_host.val(),
			solr_index: solr_elements.solr_index.val(),
			analyzer_tokenizer: solr_elements.analyzer_tokenizer.val()
		};

		$.ajax({
			method: 'POST',
			url: OC.generateUrl(solr_settings.settings_url),
			data: {
				data: data
			}
		}).done(function (res) {
			solr_settings.updateSettingPage(res);
		});

	}


};
