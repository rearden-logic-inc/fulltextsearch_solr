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

/** global: OCA */
/** global: fts_admin_settings */
/** global: solr_settings */


var solr_elements = {
	solr_div: null,
	solr_host: null,
	solr_index: null,
	analyzer_tokenizer: null,


	init: function () {
		solr_elements.solr_div = $('#solr');
		solr_elements.solr_host = $('#solr_host');
		solr_elements.solr_index = $('#solr_index');
		solr_elements.analyzer_tokenizer = $('#analyzer_tokenizer');

		solr_elements.solr_host.on('input', function () {
			fts_admin_settings.tagSettingsAsNotSaved($(this));
		}).blur(function () {
			solr_settings.saveSettings();
		});

		solr_elements.solr_index.on('input', function () {
			fts_admin_settings.tagSettingsAsNotSaved($(this));
		}).blur(function () {
			solr_settings.saveSettings();
		});

		solr_elements.analyzer_tokenizer.on('input', function () {
			fts_admin_settings.tagSettingsAsNotSaved($(this));
		}).blur(function () {
			solr_settings.saveSettings();
		});
	}


};


