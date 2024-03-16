class ElementInfo {
    #elementId;
    #elementName;
    #minLength;
    #maxLength;
    #regexPattern;
    #regexFailureMessage;

    get elementId() {
        return this.#elementId;
    }

    get elementName() {
        return this.#elementName;
    }

    get minLength() {
        return this.#minLength;
    }

    get maxLength() {
        return this.#maxLength;
    }

    get regexPattern() {
        return this.#regexPattern;
    }

    get regexFailureMessage() {
        return this.#regexFailureMessage;
    }

    get isValueLengthInRange() {
		if (this.isElementNull)
			return true;

        const length = ElementManager.getValue(this.#elementId).length;
        return length >= this.#minLength && length <= this.#maxLength;
    }

    get doesValueMatchRegexPattern() {
		if (this.isElementNull)
			return true;

        // Return true if the pattern is null, evaluate it otherwise
        return this.#regexPattern == null || this.#regexPattern.test(document.getElementById(this.#elementId).value);
    }

	get isElementNull() {
		return document.getElementById(this.#elementId) == null;
	}

    constructor(elementId, elementName, minLength, maxLength, regexPattern = null, regexFailureMessage = null) {
        if (minLength <= 0 || maxLength <= 0)
            throw new TypeError("Min length and max length must be greater than zero.");

        this.#elementId           = elementId;
        this.#elementName         = elementName;
        this.#minLength           = minLength;
        this.#maxLength           = maxLength;
        this.#regexPattern        = regexPattern;
        this.#regexFailureMessage = regexFailureMessage;
    }
}

class Assertion {
    static #SUCCESSFUL_ASSERTION = new Assertion(true);

    #isTrue;
    #failureMessage;

    get isTrue() {
        return this.#isTrue;
    }

    get failureMessage() {
        return this.#failureMessage;
    }

    static get successfulAssertion() {
        return Assertion.#SUCCESSFUL_ASSERTION;
    }

    constructor(isTrue, failureMessage = null) {
        this.#isTrue         = isTrue;
        this.#failureMessage = failureMessage;
    }

    static assertAll(...assertions) {
        for (const assertion of assertions) {
            if (!assertion.isTrue)
                return assertion;
        }

        return Assertion.successfulAssertion;
    }
}

class InputValidator {
    static validateElements(...elementInfoList) {
        for (const element of elementInfoList) {
            const assertion = Assertion.assertAll(
                new Assertion(document.getElementById(element.elementId) != null, "The form is missing certain required fields, please reload the page and try again."),
                new Assertion(element.isValueLengthInRange, `${element.elementName} must be between ${element.minLength} and ${element.maxLength} characters.`),
                new Assertion(element.doesValueMatchRegexPattern, element.regexFailureMessage)
            );

            if (!assertion.isTrue)
                return assertion;
        }

        return Assertion.successfulAssertion;
    }
}

class ElementManager {
    static getValue(elementId) {
        return document.getElementById(elementId).value;
    }

    static getNumericValue(elementId) {
        return Number(ElementManager.getValue(elementId));
    }

    static isRadioButtonChecked(elementId) {
        return document.getElementById(elementId).checked;
    }
}

class Constants {
	static #MINIMUM_USER_AGE = 18;
	static #MAXIMUM_USER_AGE = 80;

    static #AGE_NOT_IN_RANGE_MESSAGE = `You must be at least ${Constants.minimumUserAge} years old, and at most ${Constants.maximumUserAge} years old in order to register an account.`;

    static get ageNotInRangeMessage() {
        return Constants.#AGE_NOT_IN_RANGE_MESSAGE;
    }

    static get minimumUserAge() {
        return Constants.#MINIMUM_USER_AGE;
    }

    static get maximumUserAge() {
        return Constants.#MAXIMUM_USER_AGE;
    }
}
