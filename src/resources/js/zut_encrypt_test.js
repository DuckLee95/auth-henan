function _etd2(_p0, _p1) {
    try {
        var _p2 = encryptAES(_p0, _p1);
        $("#casLoginForm")
            .find("#passwordEncrypt").val(_p2);
    } catch (e) {
        $("#casLoginForm")
            .find("#passwordEncrypt").val(_p0);

    }
}

_etd2(password.val(), casLoginForm.find("#pwdDefaultEncryptSalt").val());
