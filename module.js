if (!M.block_quiz_organizer) {
    M.block_quiz_organizer = {
        /**
         * @memberOf M.block_quiz_organizer
         */
        init_organizer: function(Y) {
            this.Y = Y;

            Y.all('#page-content, #maincontent, .region-content').setStyle('overflow', 'visible');

            Y.one("#quizorg").invoke("reset");

            //this.resize_table();

            Y.on("click", function(e) {
                this.resize_table();
            }, ".resizetable", this);

            Y.on("click", function(e) {
                var elt = e.target;
                this.copy(elt.getData("name"), elt.getData("type"));
            }, ".copybutton", this);
//            Y.all(".copybutton").set("disabled", 1);

//            Y.on("click", function(e) {
//                this.update_copy_buttons();
//            }, ".quizcheck, .checkall", this);
//
//            Y.on("click", function(e) {
//                this.disable_datetime(e.target.getData("prefix"), e.target.get("checked"));
//            }, ".datetime-no", this);
        },

        resize_table: function() {
            var Y = this.Y;

            Y.all(".no-overflow").setStyle("width", (Y.one("body").get("winWidth") - 300) + "px");
            Y.all(".no-overflow").setStyle("height", (Y.one("body").get("winHeight") - 100) + "px");
        },

        init_setting: function(Y) {
            Y.on(
                    "click", function(e) {
                        var newstate = Y.one("#checkall").get("checked");
                        if (newstate) {
                            Y.all("input.settingcheckbox").each(
                                    function() {
                                        this.set("checked", "checked");
                                    });
                        } else {
                            Y.all("input.settingcheckbox").each(
                                    function() {
                                        this.set("checked", "");
                                    });
                        }
                    }, "#checkall");
        },

        update_copy_buttons: function() {
            var Y = this.Y;

            if (Y.one(".quizcheck:checked")) {
                Y.all(".copybutton").removeAttribute("disabled");
            } else {
                Y.all(".copybutton").set("disabled", 1);
            }
        },

        check_quizzes: function(state) {
            void(d=document);
            void(el=d.getElementsByTagName('INPUT'));
            for(var i=0;i<el.length;i++) {
                if (el[i].name.substr(0, 4) == "chk_") {
                    void(el[i].checked=state);
                }
            }
        },

        disable_datetime: function(prefix, disabled) {
            var Y = this.Y;

            if (disabled) {
                Y.one(prefix + "_year").set("disabled", 1);
                Y.one(prefix + "_month").set("disabled", 1);
                Y.one(prefix + "_day").set("disabled", 1);
                Y.one(prefix + "_hour").set("disabled", 1);
                Y.one(prefix + "_min").set("disabled", 1);
            } else {
                Y.one(prefix + "_year").removeAttribute("disabled");
                Y.one(prefix + "_month").removeAttribute("disabled");
                Y.one(prefix + "_day").removeAttribute("disabled");
                Y.one(prefix + "_hour").removeAttribute("disabled");
                Y.one(prefix + "_min").removeAttribute("disabled");
            }
        },

        get_checked_ids: function() {
            var Y = this.Y;
            var elts = Y.all("input:checked");
            var ids = [];

            elts.each(function(node) {
                if (node.get("name").substr(0, 4) == "chk_") {
                    ids.push(node.get("name").substr(4));
                }
            });

            return ids;
        },

        copy: function(colname, type) {
            var Y = this.Y;
            var i, j;

            var eltname = "#q_batch_" + colname;
            var newvalues;

            var ids = this.get_checked_ids();

            switch (type) {
            case "datetime":
                newvalues = {
                    year: Y.one(eltname + "_year").get("value"),
                    month: Y.one(eltname + "_month").get("value"),
                    day: Y.one(eltname + "_day").get("value"),
                    hour: Y.one(eltname + "_hour").get("value"),
                    min: Y.one(eltname + "_min").get("value"),
                    none: Y.one(eltname + "_none").get("checked")
                };

                for (i = 0; i < ids.length; i++) {
                    eltname = "#q_" + ids[i] + "_" + colname;
                    Y.one(eltname + "_none").set("checked", newvalues["none"]);
                    if (!newvalues["none"]) {
                        Y.one(eltname + "_year").set("value", newvalues["year"]);
                        Y.one(eltname + "_month").set("value", newvalues["month"]);
                        Y.one(eltname + "_day").set("value", newvalues["day"]);
                        Y.one(eltname + "_hour").set("value", newvalues["hour"]);
                        Y.one(eltname + "_min").set("value", newvalues["min"]);
                    }
                    this.disable_datetime(eltname, newvalues["none"]);
                }
                break;

            case "duration":
                newvalues = {
                    number: Y.one(eltname + "_number").get("value"),
                    timeunit: Y.one(eltname + "_timeunit").get("value"),
                    enabled: Y.one(eltname + "_enabled").get("checked")
                };

                for (i = 0; i < ids.length; i++) {
                    eltname = "#q_" + ids[i] + "_" + colname;
                    Y.one(eltname + "_enabled").set("checked", newvalues["enabled"]);
                    if (newvalues["enabled"]) {
                        Y.one(eltname + "_number").set("value", newvalues["number"]);
                        Y.one(eltname + "_timeunit").set("value", newvalues["timeunit"]);
                    }
                }
                break;

            case "feedback":
                newvalues = {},
                newvalues.text = [];
                newvalues.boundaries = [];
                for (i = 0;; i++) {
                    eltname = "#q_batch_" + colname;
                    if (!Y.one(eltname + "_text_" + i )) {
                        var feedbackrepeat = i;
                        break;
                    }

                    newvalues.text[i] = Y.one(eltname + "_text_" + i).get("value");
                    if (node = Y.one(eltname+"_boundaries_"+i))
                        newvalues.boundaries[i] = node.get("value");
                }

                for (i = 0; i < ids.length; i++) {
                    eltname = "#q_" + ids[i] + "_" + colname;
                    for (j = 0; j < feedbackrepeat; j++) {
                        Y.one(eltname + "_text_" + j).set("value", newvalues.text[j]);
                        if (j < feedbackrepeat - 2)
                            Y.one(eltname + "_boundaries_" + j).set("value", newvalues.boundaries[j]);
                    }
                }
                break;

            case "conditions":
                newvalues = [];
                for (i = 0;; i++) {
                    eltname = "#q_batch_"+colname+"_"+i;
                    if (!Y.one(eltname+"_conditiongradeitemid")) {
                        var conditionrepeat = i;
                        break;
                    }

                    newvalues[i] = [];
                    newvalues[i]["conditiongradeitemid"] = Y.one(eltname + "_conditiongradeitemid").get("value");
                    newvalues[i]["conditiongrademin"] = Y.one(eltname + "_conditiongrademin").get("value");
                    newvalues[i]["conditiongrademax"] = Y.one(eltname + "_conditiongrademax").get("value");
                }

                for (i = 0; i < ids.length; i++) {
                    for (j = 0; j < conditionrepeat; j++) {
                        eltname = "#q_" + ids[i] + "_" + colname + "_" + j;
                        Y.one(eltname + "_conditiongradeitemid").set("value", newvalues[j]["conditiongradeitemid"]);
                        Y.one(eltname + "_conditiongrademin").set("value", newvalues[j]["conditiongrademin"]);
                        Y.one(eltname + "_conditiongrademax").set("value", newvalues[j]["conditiongrademax"]);
                    }
                }
                break;

            case "userfields":
                newvalues = [];
                for (i = 0;; i++) {
                    eltname = "#q_batch_"+colname+"_"+i;
                    if (!Y.one(eltname+"_conditionfield")) {
                        var userfieldrepeat = i;
                        break;
                    }

                    newvalues[i] = {
                        conditionfield: Y.one(eltname+"_conditionfield").get("value"),
                        conditionfieldoperator: Y.one(eltname+"_conditionfieldoperator").get("value"),
                        conditionfieldvalue: Y.one(eltname+"_conditionfieldvalue").get("value")
                    };
                }

                for (i = 0; i < ids.length; i++) {
                    for (j = 0; j < userfieldrepeat; j++) {
                        eltname = "#q_"+ids[i]+"_"+colname+"_"+j;
                        Y.one(eltname+"_conditionfield").set("value", newvalues[j].conditionfield);
                        Y.one(eltname+"_conditionfieldoperator").set("value", newvalues[j].conditionfieldoperator);
                        Y.one(eltname+"_conditionfieldvalue").set("value", newvalues[j].conditionfieldvalue);
                    }
                }
                break;

            case "penalties":
                prefix = "q_batch_";
                newvalues = {
                    penaltylimitdate_year: document.getElementById(prefix + "penaltylimitdate_year").value,
                    penaltylimitdate_month: document.getElementById(prefix + "penaltylimitdate_month").value,
                    penaltylimitdate_day: document.getElementById(prefix + "penaltylimitdate_day").value,
                    penaltylimitdate_hour: document.getElementById(prefix + "penaltylimitdate_hour").value,
                    penaltylimitdate_min: document.getElementById(prefix + "penaltylimitdate_min").value,
                    penaltylimitdate_nochange: Y.one('#'+prefix+'penaltylimitdate_nochange').get('checked'),
                    penaltyshowlimitdate: document.getElementById(prefix + "penaltyshowlimitdate").value
                };
                for (i = 0; i < 4; i++) {
                    newvalues["penaltydays_" + i] = document.getElementById(prefix + "penaltydays_" + i).value;
                    newvalues["penaltypercent_" + i] = document.getElementById(prefix + "penaltypercent_" + i).value;
                    newvalues["penaltytype_" + i] = document.getElementById(prefix + "penaltytype_" + i).value;
                    newvalues["penaltyenable_" + i] = document.getElementById(prefix + "penaltyenable_" + i).checked;
                }

                ids = this.get_checked_ids();
                for (i = 0; i < ids.length; i++) {
                    prefix = "q_" + ids[i] + "_";
                    if (newvalues.penaltylimitdate_nochange == false) {
                        document.getElementById(prefix + "penaltylimitdate_year").value = newvalues["penaltylimitdate_year"];
                        document.getElementById(prefix + "penaltylimitdate_month").value = newvalues["penaltylimitdate_month"];
                        document.getElementById(prefix + "penaltylimitdate_day").value = newvalues["penaltylimitdate_day"];
                        document.getElementById(prefix + "penaltylimitdate_hour").value = newvalues["penaltylimitdate_hour"];
                        document.getElementById(prefix + "penaltylimitdate_min").value = newvalues["penaltylimitdate_min"];
                    }
                    document.getElementById(prefix + "penaltyshowlimitdate").value = newvalues["penaltyshowlimitdate"];
                    for (j = 0; j < 4; j++) {
                        document.getElementById(prefix + "penaltydays_" + j).value = newvalues["penaltydays_" + j];
                        document.getElementById(prefix + "penaltypercent_" + j).value = newvalues["penaltypercent_" + j];
                        document.getElementById(prefix + "penaltytype_" + j).value = newvalues["penaltytype_" + j];
                        document.getElementById(prefix + "penaltyenable_" + j).checked = newvalues["penaltyenable_" + j];
                    }
                }
                break;

            default:
                var newvalue = Y.one(eltname).get("value");

                ids = this.get_checked_ids();
                for (i = 0; i < ids.length; i++) {
                    eltname = "#q_" + ids[i] + "_" + colname;
                    Y.one(eltname).set("value", newvalue);
                }
            }
        }
    };
}
