'use strict';

/**
 * Available locales fetcher
 *
 * @author                 Nicolas SOUFFLEUR, Akeneo Expert <contact@nicolas-souffleur.com>
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define([
    'jquery',
    'underscore',
    'oro/translator',
    'pim/fetcher-registry',
    'pim/job/common/edit/field/select'
], function (
    $,
    _,
    __,
    FetcherRegistry,
    SelectField
) {
    return SelectField.extend({
        /**
         * {@inherit}
         */
        configure: function () {
            return $.when(
                FetcherRegistry.getFetcher('locale').fetchActivated(),
                SelectField.prototype.configure.apply(this, arguments)
            ).then(function (availableLocales) {
                    this.config.options = this.formatChoices(availableLocales);
                }.bind(this));
        },
        /**
         * @param {Array} locales
         */
        formatChoices: function (locales) {
            return _.object(
                _.pluck(locales, 'code'),
                _.pluck(locales, 'label')
            );
        },
    });
});
