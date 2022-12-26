<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-mQ93GR66B00ZXjt0YO5KlohRA5SY2XofN4zfuZxLkoj1gXtW8ANNCe9d5Y3eG5eD" crossorigin="anonymous"></script>

</head>

<body>
    <div class="container mt-5">




        <div class="row">
            <div class="col-sm-6">
            <form method="post" action="relatoriocarrosplacas.php" >
            <div class="mb-3">
                <label for="placa" class="form-label">PLACA</label>
                <input type="text" class="form-control" id="placa" name="placa" aria-describedby="emailHelp">
                <div id="placa" class="form-text"></div>
            </div>

            <div class="row">
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label for="dtInicial" class="form-label">Data Inicial</label>
                        <input type="text" class="form-control" id="dtInicial" name="dtInicial">
                    </div>


                </div>
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label for="dtFinal" class="form-label">Data Final</label>
                        <input type="text" class="form-control" id="dtFinal" name="dtFinal">
                    </div>


                </div>

            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

            </div>
        </div>




    </div>


</body>

</html>