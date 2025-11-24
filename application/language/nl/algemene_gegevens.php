<div id="clear">
<style type="text/css">

</style>
    <?php form_open_multipart("/bmi_technisch/algemene_gegevens/".$id, 'name="algemene_gegevens" id="algemene_gegevens"')?>
    <form enctype='multipart/form-data' name="algemene_gegevens" id="algemene_gegevens" action="/bmi_technisch/algemene_gegevens/<?php echo $id ?>"
          method="post" class="longform form-horizontal">
        <?php
        if (validation_errors() || isset($error)) : ?>
            <div class="alert alert-danger" role="alert">
                <ul>
                    <?php
                    foreach ($error as $errorLine) {
                        echo '<li>' . $errorLine . '</li>';
                    }
                    ?></ul>
            </div>
        <?php endif;
        if ($this->session->flashdata('message')) {
            echo "<div class='alert alert-success' role='alert'><i class='fa fa-check-circle'></i> " . $this->session->flashdata('message') . "</div>";
        }
        ?>
        <input type="hidden" name="id"
               value="<?php echo isset($installation->BMIInstallationDataRow->id) ? $installation->BMIInstallationDataRow->id : '' ?>"/>

        <div class="row">
            <h3 class="col-sm-offset-2">Algemene gegevens</h3>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-2" for="centrale">Hoofd Brandmeldcentrale:</label>

            <div class="controls col-sm-5 col-lg-3 ">
                <input type="text" id="centrale" name="centrale_naam" value="<?php echo set_value(
                    'centrale_naam',
                    $installation->BMIInstallationDataRow->centraleNaam
                ) ?>" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-2" for="centrale_locatie">Locatie centrale:</label>

            <div class="controls col-sm-5 col-lg-3 ">
                <input type="text" id="centrale_locatie" name="centrale_locatie" value="<?php echo set_value(
                    'centrale_locatie',
                    $installation->BMIInstallationDataRow->centraleLocatie
                ) ?>" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2" for="centrale_versie">Firmware versie:</label>

            <div class="controls col-sm-5 col-lg-3">
                <input type="text" id="centrale_versie" name="centrale_versie" value="<?php echo set_value(
                    'centrale_versie',
                    $installation->BMIInstallationDataRow->centraleVersie
                ) ?>" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2" for="centrale_versie">Configuratie bestand:</label>
            <?php if ($installation->BMIInstallationDataRow->conf_file_hashed_name == '') { ?>
            <div class="controls col-sm-5 col-lg-3">
                <span class="input-group-btn">
                        <span id="config_file_button" class="col-sm-4 btn btn-default btn-file" onclick="">
                            Bladeren
                        </span>
                    <input class="btn btn-default btn-file btn-disabled col-sm-8" id="config_file_name" type="text" disabled="" value="Nog geen bestand.">
                                    <input style="opacity: -99999;overflow: hidden!important" id="config_file" name="config_file" type="file">
                </span>
                <p class="help-block" style="margin-bottom: 0;">De maximale bestandsgrootte is 5MB. Toegestane bestandstypes zijn .fwe .ncf en .tdf</p>
        </div>
            <?php } else { ?>
                <div id="conf_file_buttons" class="controls col-sm-5 col-lg-3 ">
                    <input class="btn btn-default btn-file btn-disabled col-sm-12" id="config_file_name" type="text" disabled="" value="<?php echo $installation->BMIInstallationDataRow->conf_file_original_name;?>">
                        <a class="col-sm-6 col-lg-6 btn btn-success" href="/bmi_technisch/config_file/download/<?php echo $installation->BMIInstallationDataRow->id; ?>"><i class="fa fa-download"></i> Download</a>

                    <a href="javascript:;" data-fancybox id="delete_conf_file_popup" class="col-sm-6 col-lg-6 btn btn-danger"
                       data-fancybox data-src="#confirmConfDeleteBox"><i class="fa fa-trash"></i> Verwijder</a>
                </div>
            <?php } ?>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2">Soort installatie:</label>

            <div class="col-sm-5 col-lg-3 controls">
                <div class="radio-inline"><label><input type="radio" name="installatie_soort"
                                                        value="Analoog" <?php echo ef2_set_radio(
                                                            'installatie_soort',
                                                            'Analoog',
                                                            $installation->BMIInstallationDataRow->installatieSoort
                                                        ) ?> />Analoog</label></div>
                <div class="radio-inline"><label><input type="radio" name="installatie_soort"
                                                        value="Adresseerbaar" <?php echo ef2_set_radio(
                                                            'installatie_soort',
                                                            'Adresseerbaar',
                                                            $installation->BMIInstallationDataRow->installatieSoort
                                                        ) ?> />Conventioneel</label></div>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2" for="installatie_datum_eerste_onderhoud">Datum eerste
                onderhoud:</label>

            <div class="controls col-sm-5 col-lg-3">
                <input type="text" id="installatie_datum_eerste_onderhoud" name="installatie_datum_eerste_onderhoud"
                       class="datepicker form-control"
                       value="<?php
                        if (isset($installation->BMIInstallationDataRow->datumEersteOnderhoud)) {
                            echo set_value(
                                'installatie_datum_eerste_onderhoud',
                                date('d-m-Y', strtotime($installation->BMIInstallationDataRow->datumEersteOnderhoud))
                            );
                        } ?>"/>
            </div>
        </div>




        <div class="form-group">
            <label class="control-label col-sm-2" for="systeembeschikbaarheid_richtlijn">Systeembeschikbaarheid:</label>

            <div class="controls col-sm-5 col-lg-3">
                <div class="input-group">
                    <input type="text" id="systeembeschikbaarheid_richtlijn" name="systeembeschikbaarheid_richtlijn"
                           value="<?php echo set_value(
                               'systeembeschikbaarheid_richtlijn',
                               $installation->BMIInstallationDataRow->systeembeschikbaarheid_richtlijn
                           ) ?>" class="form-control number"/>
                    <span class="input-group-addon"> % </span>
                </div>
                <p class="help-block">Indien u de standaardwaarde van 99.7% aanpast dient dit wel overeen te komen met
                    het PvE of NvA.</p>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2" for="youtube">Youtube link:</label>

            <div class="controls col-sm-5 col-lg-3">
                <input type="text" id="youtube" name="youtube" value="<?php echo set_value(
                    'youtube',
                    $installation->BMIInstallationDataRow->youtube
                ) ?>" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-sm-2" for="count_alerts">Huidige tellerstand:</label>

            <div class="controls col-sm-5 col-lg-3">
                <input type="text" id="count_alerts" name="count_alerts"
                    <?php echo ($installation->BMIInstallationDataRow->count_alerts > 0) ? ' readonly="true"' : ''; ?>
                       value="<?php echo set_value('count_alerts', $installation->BMIInstallationDataRow->count_alerts) ?>"
                       class="form-control"/>
                <?php if ($installation->BMIInstallationDataRow->count_alerts > 0) : ?>
                    <p style="padding: 15px 0 0 0">U kunt de tellerstand bijhouden via de BMI periodieke maandelijkse controle. </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-actions" style="margin-top: 50px">
            <?php if (permission($this->session->userdata('user_id'), 'FORM_WRITE_BMI_INSTALLATION')) { ?>
                <button class="btn btn-primary" id="btnSubmit" name="btnSubmit" type="submit">Opslaan <i class="submit fa fa-spinner fa-spin"></i></button>
                <?php if ($this->input->get('frominbox')) : ?>
                    <a href="/inbox/back" type="button" class="btn" name="btnCancel">Annuleren</a>
                <?php else : ?>
                    <a href="/installations/overview/<?php echo $id; ?>" type="button" class="btn" name="btnCancel">Annuleren</a>
                <?php endif; ?>
            <?php } else { ?>
                <a href="/installations/overview/<?php echo $id; ?>" type="button" class="btn"
                   name="btnCancel">Terug</a>
            <?php } ?>
        </div>
        <br/>
    </form>
</div>
</div>



<script>
    $(document).ready(function () {
        $('#config_file_button').click(function () {
            $('#config_file').click();
        });

        $('#config_file').change(function () {
            var filename = $(this)[0].files.length ? $(this)[0].files[0].name : "";
            var namelement = $('#config_file_name');

            namelement.val(filename);

        });

        $( ".submit" ).hide();
        $("#btnSubmit").click(function(){
            $(".submit").show();
        });



        $('#delete_conf_file_popup').click(function () {
            $.fancybox.open(
                '<div id="confirmConfDeleteBox"><p>Weet u zeker dat u het configuratie bestand wilt verwijderen?</p><a href="#" id="delete_conf_file" onclick="deleteConfFile();$.fancybox.close();" class="col-sm-6 col-lg-6 btn btn-danger">Verwijderen</a><a href="#" id="delete_conf_file" onclick="$.fancybox.close();" class="col-sm-6 col-lg-6 btn btn-primary">Annuleren</></div>')
        })

    });

</script>
<script src="/assets/js/deleteConfigFile.js"></script>
